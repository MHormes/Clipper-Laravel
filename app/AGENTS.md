# Backend Logic — Agent Instructions

## Domain Constraints
- **Pattern**: Follow the **Action** pattern for complex logic (`app/Actions`).
- **Services**: Use **Services** for reusable business logic (`app/Services`).
- **Controllers**: Keep thin; delegate to Actions or Services.
- **Models**: Use Eloquent relationships and keep logic out of Models when possible.
- **Type Safety**: Use type hints for all method arguments and return types.
