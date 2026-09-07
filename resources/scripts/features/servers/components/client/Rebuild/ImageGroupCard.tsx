import ImageIconDisplay from '@/features/images/components/ImageIconDisplay'
import { ImageGroup } from '@/types/image.ts'
import { IconBox } from '@tabler/icons-react'

import { Badge } from '@/components/ui/Badge'
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import { RadioGroupItem } from '@/components/ui/RadioGroup'

interface Props {
    group: ImageGroup
}

/**
 * A selectable card for one OS family. The border, padding and the selected
 * tint all come from `FieldLabel` wrapping a `Field` — that pairing is what
 * turns the label into a radio card (`rounded-lg border p-2.5`, then
 * `border-primary/30 bg-primary/5` once the radio inside reports
 * `data-checked`). Keep the `Field` a direct child or none of it applies.
 */
const ImageGroupCard = ({ group }: Props) => {
    const id = `image-group-${group.uuid}`

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation={'horizontal'}>
                <RadioGroupItem id={id} value={group.uuid} />
                <ImageIconDisplay
                    icon={group.icon}
                    defaultIcon={IconBox}
                    className={'text-muted-foreground size-5 shrink-0'}
                />
                <FieldContent>
                    <FieldTitle>
                        {group.name}
                        {/* Non-admins never receive these groups at all
                            (SettingsController::getImageGroups filters
                            them), so this only ever marks a group an admin
                            can see and a customer cannot. */}
                        {group.isAdminOnly && (
                            <Badge variant={'secondary'}>Admin only</Badge>
                        )}
                    </FieldTitle>
                    {group.description && (
                        <FieldDescription>{group.description}</FieldDescription>
                    )}
                </FieldContent>
            </Field>
        </FieldLabel>
    )
}

export default ImageGroupCard
