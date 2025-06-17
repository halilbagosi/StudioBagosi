# Email Notification System - Studio Bagosi

## Overview

An email notification system has been implemented for Studio Bagosi to automatically send email notifications to users when their booking status changes. The system supports both registered users and guest bookings.

## Features

- **Automatic Notifications**: Emails are sent automatically when booking status changes
- **Status-Based Triggers**: Notifications are sent for:
  - ✅ **Confirmed** bookings
  - ❌ **Cancelled** bookings  
  - ⭐ **Completed** bookings
- **Multi-language Support**: Emails are sent in Albanian (matching the website language)
- **Guest Booking Support**: Works for both registered users and guest bookings
- **Admin Test Interface**: Built-in testing tools for administrators

## Files Added/Modified

### New Files
1. **`config/email_notifications.php`**: Core email notification system
2. **`admin_83926483499/test_email.php`**: Admin testing interface
3. **`EMAIL_NOTIFICATION_SYSTEM.md`**: This documentation

### Modified Files
1. **`admin_83926483499/bookings.php`**: Updated to trigger email notifications
2. **`admin_83926483499/includes/navbar.php`**: Added link to test page

## How It Works

### 1. Status Change Detection
When an admin updates a booking status through the admin panel:
- The system captures the old status before updating
- Compares it with the new status
- Triggers email notification if the status actually changed

### 2. Email Content Generation
- Subject line is generated based on the new status
- Email body includes:
  - Booking details (package, date, time, price)
  - Status-appropriate message
  - Studio contact information and social media links

### 3. Recipient Detection
- **Registered Users**: Uses the email from the `users` table
- **Guest Bookings**: Extracts email from the booking notes field

### 4. Email Delivery
- Uses PHP's built-in `mail()` function
- UTF-8 encoding for Albanian characters
- Professional email headers with Studio branding

## Email Templates

### Confirmed Booking
```
Subject: Rezervimi juaj është konfirmuar - Studio Bagosi

Përshëndetje [Name],

Kemi kënaqësinë t'ju njoftojmë se rezervimi juaj është konfirmuar!

Detajet e rezervimit:
• Paketa: [Package Name]
• Data e eventit: [Date]
• Ora e eventit: [Time]
• Çmimi: €[Price]

Do t'ju kontaktojmë së shpejti për të diskutuar detajet e mëtejshme...
```

### Cancelled Booking
```
Subject: Rezervimi juaj është anulluar - Studio Bagosi

Përshëndetje [Name],

Na vjen keq t'ju njoftojmë se rezervimi juaj është anulluar.

Detajet e rezervimit të anulluar:
• Paketa: [Package Name]
• Data e eventit: [Date]
• Ora e eventit: [Time]

Nëse keni pyetje ose dëshironi të rezervoni përsëri...
```

### Completed Service
```
Subject: Shërbimi juaj është përfunduar - Studio Bagosi

Përshëndetje [Name],

Faleminderit që na zgjodhët për eventin tuaj të veçantë!

Shërbimi për rezervimin tuaj është përfunduar me sukses:
• Paketa: [Package Name]
• Data e eventit: [Date]

Do t'ju dërgojmë fotot/videot e përpunuara në kohën e caktuar...
```

## Admin Interface

### Booking Management
- Navigate to **Admin Panel > Bookings**
- Select a booking and click "Edit"
- Change the status and add notes
- System automatically sends notification email

### Success Messages
- **Green Alert**: "Booking status updated successfully and notification email sent to client!"
- **Yellow Alert**: "Booking status updated successfully, but failed to send notification email to client. Please contact them manually."

### Testing Tools
- Navigate to **Admin Panel > Test Email**
- Send test emails to any address
- Test booking notifications using existing bookings
- View system configuration and status information

## Technical Details

### Email Configuration
- **From**: Studio Bagosi <noreply@studiobagosi.com>
- **Reply-To**: info@studiobagosi.com
- **Method**: PHP mail() function
- **Encoding**: UTF-8
- **Content-Type**: text/plain

### Database Integration
The system queries the following tables:
- `bookings`: Main booking information
- `users`: Registered user details
- `packages`: Package information for emails

### Error Handling
- Failed email attempts are logged to PHP error log
- Admin receives feedback about email delivery status
- System continues to function even if email delivery fails

## Server Requirements

### PHP Configuration
- PHP `mail()` function must be enabled
- Server must be configured to send outbound emails
- No additional PHP extensions required

### SMTP Alternative
For better email delivery, consider configuring:
- PHPMailer with SMTP
- SendGrid, Mailgun, or similar email service
- This would require modifying the `sendEmail()` function in `config/email_notifications.php`

## Troubleshooting

### Emails Not Being Sent
1. Check server email configuration
2. Verify PHP `mail()` function is enabled
3. Check spam/junk folders
4. Use the admin test interface to debug
5. Check PHP error logs for detailed error messages

### Guest Booking Issues
- Ensure guest bookings have email in notes field in format: "Guest booking via contact: email@example.com"
- The system uses regex to extract emails from notes

### Status Changes Not Triggering
- Only status changes to "confirmed", "cancelled", or "completed" trigger emails
- "pending" status changes do not send notifications
- System checks that status actually changed (prevents duplicate emails)

## Future Enhancements

Potential improvements could include:
- HTML email templates with better formatting
- Email preferences for users (opt-in/opt-out)
- SMS notifications
- Email delivery confirmation tracking
- Scheduled reminder emails
- Multiple language support based on user preference

## Support

For technical support with the email system:
1. Use the built-in test interface first
2. Check server email logs
3. Verify email addresses are valid
4. Test with different email providers

---

**Studio Bagosi Email Notification System**  
Implemented: 2024  
Version: 1.0 