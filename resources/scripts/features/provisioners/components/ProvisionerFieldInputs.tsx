import type { ProvisionerField } from '@/features/provisioners/types.ts'

import { FieldGroup } from '@/components/ui/Field'
import { InputForm } from '@/components/ui/Forms'

interface Props {
    fields: ProvisionerField[]
}

/**
 * One input per value the image's provisioners ask for, under
 * `provisionerFields.<key>`. The key is snake_case and stays that way: it is
 * what the request sends and what the script's variable is named after.
 */
const ProvisionerFieldInputs = ({ fields }: Props) => (
    <FieldGroup>
        {fields.map(field => (
            <InputForm
                key={field.key}
                name={`provisionerFields.${field.key}`}
                label={
                    field.required ? field.label : `${field.label} (optional)`
                }
                autoComplete={'off'}
                maxLength={1000}
            />
        ))}
    </FieldGroup>
)

export default ProvisionerFieldInputs
