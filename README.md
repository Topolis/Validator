# Validator
A complex validator that applies a yaml based schema file to a multi dimensional array

## Syntax
A schema is a hirarchical Yaml file. Each level, starting from the root of the file contains one of 3 different constructs.

### Object
An object is a list of named properties. Each property can then contain other constructs.
The Yaml representation of an object construct is:
  
#### Options
- `type` defines if the property contains one sub construct or an array of sub constructs. Default: `single`
- `conditionals` a list of conditionals (need to contain a properties key if they are to replace the base object). Default: `undefined`
- `default` The default value if nothing is found. Default: `undefined` (will simply remove the property)
- `required` Defines if this property must be present. Default: `false`
- `filter` If type is `multiple`, this filter will be usedto validate the array keys. Default: `undefined`
- `options` If type is `multiple`, these are the options for the key filter. Default: `[]`
- `properties` The array of properties of this object. Default: `[]`

#### Sample
```
properties:
    one:
        ...
    two:
        ...
    three:
        ...
required: true
default: {one: A}        
```

### Listing
This defines a key value array. The difference to an object is that keys are not strictly defined but only need to pass 
a filter.

#### Options
- `conditionals` a list of conditionals (need to contain a properties key if they are to replace the base object). Default: `undefined`
- `default` The default value if nothing is found. Default: `undefined` (will simply remove the property)
- `required` Defines if this property must be present. Default: `false`
- `min` The required minimum number of array elements. Default: `false`
- `max` The required maximum number of array elements. Default: `false`
- `key` Needs to contain a Value construct with the filter to use for array keys. Default: `undefined`
- `value` Needs to contain a construct for array items. Default: `undefined`

#### Sample
```
listing:
    required: true
    min: 2
    max: 10
    key:
        ...
    value:
        ...        
```

### Value
This defines a singular value.

#### Options
- `filter` The filter to use (@see Topolis/Filter). Default: `Passthrough`
- `options` The options for this filter. Default: `[]` (Keep in mind that Validator sets the default type of filters to `single`)
- `strict` Defines if a value is allowed to be sanitized if possible or not. Default: `false`
- `default` The default value if nothing is found. Default: `undefined` (will simply remove the property)
- `required` Defines if this property must be present. Default: `false`

#### Sample
```
filter: PlainExt
options: {characters: ".-_"}
required: true
default: Pustekuchen
        
```

## Open Tasks
- BUG: Definition wrong in StatusManager (Can't reproduce. Where?)
- FR: reference definitions in other files
- FR: Auto incremental indexes in listings