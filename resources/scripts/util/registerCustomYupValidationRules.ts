import * as yup from 'yup'

yup.addMethod(yup.string, 'englishKeyboardCharacters', function () {
    return this.matches(/^[A-Za-z0-9!@#$%^&*()_+\-=[\]{}|;':",.\/<>?\\ ]*$/, {
        message: 'Invalid English keyboard characters',
        excludeEmptyString: true,
    })
})

yup.addMethod(yup.string, 'passwordRequirements', function () {
    return this.matches(
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/,
        {
            message:
                'Must contain 8 characters, 1 uppercase, 1 lowercase, 1 number and 1 special character',
            excludeEmptyString: true,
        }
    )
})

yup.addMethod(yup.string, 'ipAddress', function () {
    return this.test({
        name: 'ipAddress',
        message: 'Invalid IP address',
        test: value => {
            const ipv4Regex = /^(\d{1,3}\.){3}\d{1,3}$/
            const ipv6Regex = /^([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4}$/

            return (
                ipv4Regex.test(value as string) ||
                ipv6Regex.test(value as string)
            )
        },
    })
})

yup.addMethod(yup.string, 'hostname', function () {
    return this.test({
        name: 'hostname',
        message: 'Invalid hostname',
        test: value => {
            const hostnameRegex =
                /^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/

            return hostnameRegex.test(value as string)
        },
    })
})