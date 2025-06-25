<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Traits;

trait PassportRedirectTrait
{
    /**
     * Get the redirect URL for Passport authentication
     */
    public function getPassportRedirectUrl(): string
    {
        return config('ai-agent.passport.redirect_url', '/home');
    }

    /**
     * Handle Passport redirect logic
     */
    public function handlePassportRedirect($request)
    {
        // Check if Passport is enabled
        if (! config('ai-agent.passport.enabled', false)) {
            return redirect('/');
        }

        // Get the intended URL or fallback to configured redirect
        $intended = $request->session()->get('url.intended');
        $redirectUrl = $intended ?: $this->getPassportRedirectUrl();

        // Clear the intended URL from session
        $request->session()->forget('url.intended');

        return redirect($redirectUrl);
    }

    /**
     * Set the Passport redirect URL in session
     */
    public function setPassportRedirectUrl(string $url): void
    {
        session(['passport.redirect_url' => $url]);
    }

    /**
     * Check if user has Passport authentication
     */
    public function hasPassportAuthentication(): bool
    {
        return auth()->check() && auth()->user()->tokens()->exists();
    }
}
