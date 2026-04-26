<?php

return [
    'login_popup' => [
        'enabled' => true,
        'badge' => 'News',
        'headline' => 'Welcome to the Team!',
        'subheadline' => 'Now you can manage your profile and settings.',
        'body' => 'We are excited to have you on board. Explore your profile and update your settings to get started.',
        // 'event_date' => '',
        // 'event_time' => '10:00 AM',
        // 'event_location' => 'Yangon HQ, Level 4 Hall',
        // 'cta_text' => 'View Schedule',
        'cta_url' => '/profile',
        // 'secondary_text' => 'Maybe Later',
    ],
    'profile_photo_popup' => [
        'enabled' => true,
        'badge' => 'Profile',
        'headline' => 'Add your profile photo',
        'subheadline' => 'Make chats and reports easier to read for everyone.',
        'body' => 'Team members who already updated their profile photos are shown below. Add yours for a better and more beautiful UI experience.',
        'cta_text' => 'Update Photo',
        'cta_url' => '/profile',
        'secondary_text' => 'Later',
    ],
];
