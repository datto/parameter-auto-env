# Parameters auto-env

Composer plugin to add support for `"env-map": "auto"` to
[Incenteev/ParameterHandler](https://github.com/Incenteev/ParameterHandler)

This allows for simple deployment when configuration values are set as environment variables on CI workers but not on
development or production machines. Developers can continue with their original parameter workflow.


## Installation

`composer req datto/parameter-auto-env`

With the plugin installed you can now use the new configuration options inside of `extra.incenteev-parameters`:
- `"env-map": "auto"` to enable automatic mapping of environment variables to parameters for the file
- `"auto-env-prefix": ""` optional prefix for all environment variables (default "")
- `"auto-env-fullname": true` use full name for variable names, e.g. `parameters.database_host` instead of
`database_host` (default true)

### Basic Example:
```
"incenteev-parameters": {
    "file": "app/config/parameters.yml",
    "env-map": "auto"
}
```

### Usage with run-script:
`"env-map": "auto"` will automatically apply to any Composer install or update events that include a call to
`Incenteev/ParameterHandler`. However, to work during other events or manual script execution you must explicitly add
the call to buildMap in your scripts object before the buildParameters call. e.g.
```
"scripts": {
    "update-parameters": [
        "Datto\\Composer\\ParameterAutoEnv\\AutoEnvPlugin::buildMap",
        "Incenteev\\ParameterHandler\\ScriptHandler::buildParameters"
    ],
    "post-install-cmd": [
        "@update-parameters"
    ],
    "post-update-cmd": [
        "@update-parameters"
    ]
}
```

Execution will now function correctly when running `composer run-script update-parameters`


## Environment variable names

Each parameter detected in configured files will attempt to be auto-set by a similarly named environment variable. These
will be uppercase and any `.` is replaced with two `_`s. e.g:
- `parameters.secret_key` will expect `PARAMETERS__SECRET_KEY`
- `parameters.api-url` will expect `PARAMETERS__API-URL`
- `parameters.database.name` will expect `PARAMETERS__DATABASE__NAME`

The `composer auto-env-check` command can be run to check expected environment variable names.


## Deployment environment

Environment variables are parsed as inline Yaml values and follow [standard behaviour in Incenteev/ParameterHandler][1]
for env-map parameters.

This plugin supplies the composer command `composer auto-env-check` to ensure all parameters have a corresponding
environment variables when run, returning 0 if successful and 1 if any are missing. This can be run in the test phase
of your CI pipeline.

[1]: https://github.com/Incenteev/ParameterHandler#using-environment-variables-to-set-the-parameters


## Mixed environment

All parameters in a file must be mapped automatically on deployment if any are. If exceptions are needed these should be
placed in a separate file without the `"env-map": "auto"` setting, see: [Managing multiple ignored files][2]

### Example:
```
"incenteev-parameters": [
    {
        "file": "app/config/parameters.yml"
    },
    {
        "file": "app/config/secrets.yml",
        "env-map": "auto"
    }
]
```

[2]: https://github.com/Incenteev/ParameterHandler#managing-multiple-ignored-files
