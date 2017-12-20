# Validator
A complex validator that applies a yaml based schema file to a multi dimensional array

## Open Tasks
- Check if single allowed value or array of allowed values
- reference definitions in other files

## Bugs
- Special characters in PlainExt characters option need to be escaped to avoid conflicts with regex
- Listings need "strict_keys" option to control if a sanitized key is allowed to keep it's value
- properties that have a `null` value seem to create bad paths in StatusManager
- definition wrong in StatusManager
- Message "Invalid - Additional keys present" is -2 but sounds like -11
- Relative paths in condition parser
- Auto incremental indexes in listings
- Invalid property gets removed even when required (See type in content.yml)
