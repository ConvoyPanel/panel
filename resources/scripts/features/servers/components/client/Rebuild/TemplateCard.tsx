import { Template } from '@/types/template.ts'

import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import { RadioGroupItem } from '@/components/ui/RadioGroup'

interface Props {
    template: Template
}

/** The version half of the picker; same radio card as TemplateGroupCard. */
const TemplateCard = ({ template }: Props) => {
    const id = `template-${template.uuid}`

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation={'horizontal'}>
                <RadioGroupItem id={id} value={template.uuid} />
                <FieldContent>
                    <FieldTitle>{template.name}</FieldTitle>
                    {template.description && (
                        <FieldDescription>
                            {template.description}
                        </FieldDescription>
                    )}
                </FieldContent>
            </Field>
        </FieldLabel>
    )
}

export default TemplateCard
