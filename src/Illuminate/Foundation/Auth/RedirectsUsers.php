<?php

namespace Illuminate\Foundation\Auth;

trait RedirectsUsers
{
    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
    }
	
	public function redirectPathLogout()
	{
		if (method_exists($this, 'redirectLogoutTo')) {
			return $this->redirectLogoutTo();
		}
		
		return property_exists($this, 'redirectLogoutTo') ? $this->redirectLogoutTo : '/';
	}
}
