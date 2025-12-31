# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-12-XX

### Added
- Initial release of WACloud Laravel Package
- Device management methods (create, get, list, delete, status, QR code)
- Message sending methods (text, image, video, document)
- Facade support for easy access
- Service Provider for Laravel integration
- Configuration file with environment variable support
- Full type hints for IDE support
- Error handling with detailed response format
- Support for custom API requests (GET, POST, PUT, DELETE)
- Dynamic API key and base URL configuration
- Comprehensive documentation and examples

### Features
- ✅ Device Management - Create, view, and manage WhatsApp devices/sessions
- ✅ Send Text Message - Send text messages via WhatsApp
- ✅ Send Image Message - Send images with captions
- ✅ Send Video Message - Send videos with optional video note
- ✅ Send Document Message - Send documents (PDF, DOC, etc.)
- ✅ Facade Support - Easy access using Facade
- ✅ Service Container - Integrated with Laravel Service Container
- ✅ Config File - Configuration via config file and environment variables

## [Unreleased]

### Planned
- Webhook support for incoming messages
- Template message support
- Contact management
- Bulk message sending
- Queue support for async message sending
- Rate limiting helper
- Testing suite

