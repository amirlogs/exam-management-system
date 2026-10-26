# 🛠️ UEMS Backend API

The RESTful API service powering the University Exam Management System, developed with **Laravel 11 / 12** and **PHP 8.3+**.

## Core Responsibilities
- **Authentication & RBAC**: Laravel Sanctum stateful API tokens and permission-based gates.
- **Generative AI Assessment Engine**: Unified LLM abstraction via Prism PHP (Gemini, Claude, OpenAI).
- **Asynchronous Processing**: Background queue workers for AI question generation and student practice tutoring.
- **Data Ingestion**: Multi-tenant Excel/CSV imports with preview and validation via Maatwebsite Excel.
- **Automated API Documentation**: Scramble interactive OpenAPI schema generated at `/docs/api`.

For complete system architecture, installation, seed accounts, and testing guides, refer to the [Root README.md](../README.md).
