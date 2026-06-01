# Archive System for PSU-ALAMINOS
University Capstone Project

A document management system built with PHP that allows different user roles (admin, faculty, and student) to manage and share educational resources.

## Features

- User authentication with different access levels
- File upload and management capabilities
- Document viewing and editing with OnlyOffice integration
- Course materials organization
- Notification system
- Search functionality

## Installation

The offline environment is strictly for testing modules and debugging code.
Prerequisites
Before you begin, make sure the following are installed on your machine:
•	PHP
•	Composer
•	MySQL (or MariaDB)
•	Git
•	A PHP built-in server

Installation Steps
1. Clone the Repository
Open a terminal and run:
bashgit clone https://github.com/melodyneypes/knowhub.git
cd knowhub
2. Install PHP Dependencies
From the project root, run:
bashcomposer install
3. Set Up the Database
Create a local database and import the SQL schema:
bashmysql -u root -p -e "CREATE DATABASE knowhub_db CHARACTER SET utf8mb4;"
mysql -u root -p knowhub_db < "knowhub_db (3).sql"
Then open db.php and update it with your local database credentials.
4. Configure Local Settings
Edit the values in config files such as b2-config.php to use your local or test credentials.
Never commit real API keys or secrets to the repository.
5. Start the Local Server
Right-click the project folder and select PHP Server: Serve Project

Notes:
Run npm install only if you need frontend packages; it is not required for basic local testing.
Skip queue and background workers unless your specific test scenario requires them.


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


