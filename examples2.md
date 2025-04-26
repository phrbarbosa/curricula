# DTO (Data Transfer Objects) Module

An elegant and powerful PHP module for data validation and sanitization using DTOs with PHP 8+ attributes.

## Features

- ✨ Declarative validation using PHP 8+ attributes
- 🧹 Automatic data sanitization
- 🔄 Automatic type conversion
- 🎯 Extensible validators and sanitizers
- 🛡️ Protection against malicious data
- 📦 Easy to integrate and use

## Installation

1. Clone the repository:
```bash
git clone https://github.com/phrbarbosa/sandbox-php.git
```

2. Install dependencies:
```bash
composer install
```

## Basic Usage

### 1. Create your DTO

```php
use Pao\Dto\BaseDto;
use Pao\Dto\Attributes\Validate;
use Pao\Dto\Attributes\Sanitize;

class UserDto extends BaseDto
{
    #[Validate(RequiredValidator::class)]
    #[Validate(EmailValidator::class)]
    #[Sanitize(TrimSanitizer::class)]
    #[Sanitize(LowercaseSanitizer::class)]
    protected string $email = '';

    #[Validate(RequiredValidator::class)]
    #[Validate(MinLengthValidator::class, ['min' => 8])]
    #[Sanitize(TrimSanitizer::class)]
    protected string $password = '';
}
```

### 2. Use the DTO

```php
try {
    $userData = [
        'email' => '  USER@EXAMPLE.COM  ',
        'password' => '  SecurePass123  '
    ];

    $userDto = UserDto::fromArray($userData);
    
    // $userDto->email will be "user@example.com"
    // $userDto->password will be "SecurePass123"
    
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->getErrors();
}
```

## Available Validators

- `RequiredValidator`: Ensures field is not empty
- `EmailValidator`: Validates email format
- `MinLengthValidator`: Validates minimum length
- `MaxLengthValidator`: Validates maximum length
- `ExactLengthValidator`: Validates exact length
- `RangeValidator`: Validates numeric range

## Available Sanitizers

- `TrimSanitizer`: Removes whitespace
- `LowercaseSanitizer`: Converts to lowercase
- `UppercaseSanitizer`: Converts to uppercase
- `UcwordsSanitizer`: Capitalizes words
- `NumberSanitizer`: Cleans and formats numbers
- `StringSanitizer`: Sanitizes strings and removes dangerous characters

## Creating Custom Validators

```php
class CpfValidator implements ValidatorInterface
{
    public function validate(mixed $value, array $options = []): bool
    {
        // Your CPF validation logic here
        return true;
    }

    public function getMessage(): string
    {
        return 'Invalid CPF';
    }
}
```

## Creating Custom Sanitizers

```php
class PhoneSanitizer implements SanitizerInterface
{
    public function sanitize(mixed $value, ?BaseDto $context = null): string
    {
        // Your phone sanitization logic here
        return $value;
    }
}
```

## Practical Examples

Check out the [examples/dto](../../examples/dto) folder for complete usage examples, including:

- User registration
- Product registration
- Addresses
- Complex validations
- Custom sanitization

## Testing

Run tests with PHPUnit:

```bash
vendor/bin/phpunit tests/Dto
```

## Contributing

Contributions are welcome! Please read our contribution guidelines before submitting pull requests.

## License

This module is open-source and available under the [MIT License](../../LICENSE).
