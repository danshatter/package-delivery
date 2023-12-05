<p>Dear {{ ucfirst($user->first_name) }},</p>

<p>Thank you for registering with us.</p>

<p>Your One-Time Password is <strong>{{ $user->otp }}</strong></p>