# Archive System

A document management system built with PHP that allows different user roles (admin, faculty, and student) to manage and share educational resources.

## Features

- User authentication with different access levels
- File upload and management capabilities
- Document viewing and editing with OnlyOffice integration
- Course materials organization
- Notification system
- Search functionality

## Installation

1. Clone the repository
2. Install PHP dependencies using Composer:
3. Install JavaScript dependencies using npm:
4. Set up your database and configure the connection in `db.php`
5. Create an `.env` file based on `.env.example` (if available) and configure your environment variables
6. Ensure the `uploads` directory is writable

## Usage

1. Access the application through your web server
2. Log in with your credentials
3. Navigate using the dashboard based on your user role:
- Admin: Full system access
- Faculty: Course and document management
- Student: Document browsing and uploading

## Dependencies

- PHP 8.0+
- MySQL
- Composer packages:
- vlucas/phpdotenv
- google/apiclient
- phpmailer/phpmailer
- firebase/php-jwt
- phpoffice/phpword
- tecnickcom/tcpdf
- dompdf/dompdf
- Node.js packages:
- bootstrap

## Contributing

1. Fork the repository
2. Create a new branch for your feature
3. Commit your changes
4. Push to your branch
5. Create a pull request

## License

[Add your license information here]
