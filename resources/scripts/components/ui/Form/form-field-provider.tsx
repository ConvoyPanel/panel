import { createContext } from 'react'
import {
    Controller,
    ControllerProps,
    FieldPath,
    FieldValues,
} from 'react-hook-form'

export type FormFieldContextValue<
    TFieldValues extends FieldValues = FieldValues,
    TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
> = {
    name: TName
}

export const FormFieldContext = createContext<FormFieldContextValue>(
    {} as FormFieldContextValue
)

export const FormField = <
    TFieldValues extends FieldValues = FieldValues,
    TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
>({
    ...props
}: ControllerProps<TFieldValues, TName>) => {
    return (
        <FormFieldContext.Provider value={{ name: props.name }}>
            <Controller {...props} />
        </FormFieldContext.Provider>
    )
}
