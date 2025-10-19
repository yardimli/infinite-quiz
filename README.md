# Infinite Quiz

An intelligent, dynamic quiz generation application built with Laravel and powered by Large Language Models (LLMs). This application allows users to create unique quizzes on any topic, select from various AI models, and test their knowledge with dynamically generated questions.

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)

## Features

-   **Dynamic Quiz Generation**: Create quizzes on any subject imaginable.
-   **Multiple AI Models**: Choose from a curated list of powerful LLMs to generate questions.
-   **Adaptive Questioning**: The application keeps track of previously asked questions to ensure variety.
-   **User Authentication**: Secure user registration and login.
-   **Quiz History & Scoring**: View past quizzes, track scores, and resume incomplete quizzes from the dashboard.
-   **Customizable Theming**: Switch between light, dark, and "cupcake" themes.
-   **Responsive UI**: Built with Tailwind CSS and DaisyUI for a great experience on any device.

## Tech Stack

-   **Backend**: Laravel 10
-   **Frontend**: Blade, Tailwind CSS, DaisyUI, Vite
-   **Database**: MySQL (or any Laravel-supported database)
-   **Authentication**: Laravel Breeze

## Getting Started

Follow these instructions to get a copy of the project up and running on your local machine for development and testing purposes.

### Prerequisites

Make sure you have the following software installed on your system:

-   PHP >= 8.1
-   Composer
-   Node.js & NPM
-   A database server (e.g., MySQL, PostgreSQL)

### Installation

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/yardimli/infinite-quiz.git
    cd infinite-quiz
    ```

2.  **Install PHP dependencies:**

    ```bash
    composer install
    ```

3.  **Install NPM dependencies:**

    ```bash
    npm install
    ```

4.  **Set up your environment file:**

    Copy the example environment file and generate your application key.

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

5.  **Configure your database:**

    Open the `.env` file and update the `DB_*` variables with your database credentials.

    ```ini
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=infinte-quiz
    DB_USERNAME=root
    DB_PASSWORD=
    ```

6.  **Run database migrations:**

    This will create the necessary tables in your database.

    ```bash
    php artisan migrate
    ```

7.  **Compile frontend assets:**

    ```bash
    npm run dev
    ```

8.  **Start the development server:**

    ```bash
    php artisan serve
    ```

    Your application will now be running at `http://127.0.0.1:8000`.

## Configuration

To generate quizzes, you need to configure the API keys for the Large Language Models you intend to use. These keys should be stored securely in your `.env` file.

1.  **Open the `.env` file.**
2.  **Add your API keys.** The `app/Helpers/LlmHelper.php` file is designed to fetch these keys from the environment. For example, if you are using a service like OpenAI, you would add:

    ```ini
    OPEN_ROUTER_KEY="your-secret-api-key-here"
    ```

3.  **Update `app/Helpers/LlmHelper.php`:**

    Ensure the `LlmHelper` is configured to use the correct environment variables and API endpoints for the models you want to support.

## Usage

1.  **Register an account**: Navigate to the registration page and create a new user account.
2.  **Create a Quiz**: From the dashboard, enter a topic for your quiz (e.g., "The Renaissance" or "JavaScript Fundamentals").
3.  **Select an AI Model**: Choose one of the available LLMs from the dropdown menu.
4.  **Start the Quiz**: Click "Create New Quiz" to be taken to the quiz page. The first question will be generated automatically.
5.  **Answer Questions**: Select an answer and submit. The next unique question will be generated for you.
6.  **View Your Progress**: Return to the dashboard at any time to see your quiz history, scores, and resume any quiz.
