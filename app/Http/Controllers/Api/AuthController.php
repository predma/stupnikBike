<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const int EMAIL_CODE_TTL_MINUTES = 10;

    private const int RESEND_WAIT_SECONDS = 60;

    private ?string $lastVerificationCode = null;

    private ?string $lastMailError = null;

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->sendVerificationCode($user, true);

        return response()->json([
            'message' => 'Poslali smo kod za potvrdu emaila.',
            'email' => $user->email,
            'verification_required' => true,
            'expires_in' => self::EMAIL_CODE_TTL_MINUTES * 60,
            ...$this->localDebugCode(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (! $user || ! password_verify($data['password'], $user->password)) {
            return response()->json(['message' => 'Neispravni podaci.'], 422);
        }

        if (! $user->email_verified_at) {
            $this->sendVerificationCode($user);

            return response()->json([
                'message' => 'Email nije potvrđen. Poslali smo novi kod ako je istekao ili nije ranije poslan.',
                'email' => $user->email,
                'verification_required' => true,
            ], 403);
        }

        return $this->issueTokenResponse($user);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Neispravan kod za potvrdu.'], 422);
        }

        if ($user->email_verified_at) {
            return $this->issueTokenResponse($user);
        }

        if (
            ! $user->email_verification_code_hash ||
            ! $user->email_verification_expires_at ||
            $user->email_verification_expires_at->isPast()
        ) {
            return response()->json(['message' => 'Kod je istekao. Zatraži novi kod.'], 422);
        }

        if (! Hash::check($data['code'], $user->email_verification_code_hash)) {
            return response()->json(['message' => 'Neispravan kod za potvrdu.'], 422);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
            'email_verification_sent_at' => null,
        ])->save();

        return $this->issueTokenResponse($user);
    }

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Ako račun postoji, poslali smo novi kod.']);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email je već potvrđen.',
                'verified' => true,
            ]);
        }

        $retryAfter = $this->sendVerificationCode($user);
        if ($retryAfter !== null) {
            return response()->json([
                'message' => 'Pričekaj prije ponovnog slanja koda.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response()->json([
            'message' => 'Poslali smo novi kod.',
            'email' => $user->email,
            'verification_required' => true,
            'expires_in' => self::EMAIL_CODE_TTL_MINUTES * 60,
            ...$this->localDebugCode(),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Ako račun postoji, poslali smo kod za promjenu lozinke.',
                'email' => $data['email'],
                'expires_in' => self::EMAIL_CODE_TTL_MINUTES * 60,
            ]);
        }

        $retryAfter = $this->sendPasswordResetCode($user);
        if ($retryAfter !== null) {
            return response()->json([
                'message' => 'Pričekaj prije ponovnog slanja koda.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response()->json([
            'message' => 'Poslali smo kod za promjenu lozinke.',
            'email' => $user->email,
            'expires_in' => self::EMAIL_CODE_TTL_MINUTES * 60,
            ...$this->localDebugCode(),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::query()
            ->where('email', $data['email'])
            ->where('is_active', true)
            ->first();

        if (
            ! $user ||
            ! $user->email_verification_code_hash ||
            ! $user->email_verification_expires_at ||
            $user->email_verification_expires_at->isPast() ||
            ! Hash::check($data['code'], $user->email_verification_code_hash)
        ) {
            return response()->json(['message' => 'Neispravan ili istekao kod za promjenu lozinke.'], 422);
        }

        $user->forceFill([
            'password' => $data['password'],
            'email_verified_at' => $user->email_verified_at ?? now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
            'email_verification_sent_at' => null,
            'api_token' => null,
        ])->save();

        return response()->json([
            'message' => 'Lozinka je promijenjena. Prijavi se s novom lozinkom.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json([
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Odjavljen.']);
    }

    private function issueTokenResponse(User $user): JsonResponse
    {
        $plainToken = Str::random(64);
        $user->forceFill([
            'api_token' => hash('sha256', $plainToken),
        ])->save();

        return response()->json([
            'token' => $plainToken,
            'user' => $this->formatUser($user),
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
        ];
    }

    private function sendVerificationCode(User $user, bool $force = false): ?int
    {
        if (
            ! $force &&
            $user->email_verification_sent_at &&
            $user->email_verification_sent_at->gt(now()->subSeconds(self::RESEND_WAIT_SECONDS))
        ) {
            return max(1, self::RESEND_WAIT_SECONDS - $user->email_verification_sent_at->diffInSeconds(now()));
        }

        $code = (string) random_int(100000, 999999);
        $this->lastVerificationCode = $code;

        $user->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(self::EMAIL_CODE_TTL_MINUTES),
            'email_verification_sent_at' => now(),
        ])->save();

        if (app()->environment('local')) {
            Log::info('StupnikBike email verification code generated.', [
                'email' => $user->email,
                'code' => $code,
                'expires_at' => $user->email_verification_expires_at?->toIso8601String(),
            ]);
        }

        try {
            Mail::raw(
                "Pozdrav {$user->name},\n\nVaš StupnikBike kod za potvrdu emaila je: {$code}\n\nKod vrijedi " . self::EMAIL_CODE_TTL_MINUTES . " minuta.\n\nAko niste zatražili registraciju, zanemarite ovu poruku.",
                function ($message) use ($user): void {
                    $message
                        ->to($user->email, $user->name)
                        ->subject('StupnikBike kod za potvrdu emaila');
                }
            );
        } catch (\Throwable $exception) {
            $this->lastMailError = $exception->getMessage();
            Log::warning('StupnikBike email verification delivery failed.', [
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            if (! app()->environment('local')) {
                throw $exception;
            }
        }

        return null;
    }

    private function sendPasswordResetCode(User $user): ?int
    {
        if (
            $user->email_verification_sent_at &&
            $user->email_verification_sent_at->gt(now()->subSeconds(self::RESEND_WAIT_SECONDS))
        ) {
            return max(1, self::RESEND_WAIT_SECONDS - $user->email_verification_sent_at->diffInSeconds(now()));
        }

        $code = (string) random_int(100000, 999999);
        $this->lastVerificationCode = $code;

        $user->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(self::EMAIL_CODE_TTL_MINUTES),
            'email_verification_sent_at' => now(),
        ])->save();

        if (app()->environment('local')) {
            Log::info('StupnikBike password reset code generated.', [
                'email' => $user->email,
                'code' => $code,
                'expires_at' => $user->email_verification_expires_at?->toIso8601String(),
            ]);
        }

        try {
            Mail::raw(
                "Pozdrav {$user->name},\n\nVaš StupnikBike kod za promjenu lozinke je: {$code}\n\nKod vrijedi " . self::EMAIL_CODE_TTL_MINUTES . " minuta.\n\nAko niste zatražili promjenu lozinke, zanemarite ovu poruku.",
                function ($message) use ($user): void {
                    $message
                        ->to($user->email, $user->name)
                        ->subject('StupnikBike kod za promjenu lozinke');
                }
            );
        } catch (\Throwable $exception) {
            $this->lastMailError = $exception->getMessage();
            Log::warning('StupnikBike password reset delivery failed.', [
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            if (! app()->environment('local')) {
                throw $exception;
            }
        }

        return null;
    }

    private function localDebugCode(): array
    {
        if (! app()->environment('local') || ! $this->lastVerificationCode) {
            return [];
        }

        return [
            'debug_code' => $this->lastVerificationCode,
            'mail_sent' => $this->lastMailError === null,
            'mail_error' => $this->lastMailError,
        ];
    }
}
