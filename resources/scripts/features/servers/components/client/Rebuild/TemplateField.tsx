import TemplateCard from '@/features/servers/components/client/Rebuild/TemplateCard'
import { TemplateGroup } from '@/types/template-group.ts'

import { Empty, EmptyDescription, EmptyHeader } from '@/components/ui/Empty'
import {
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { RadioGroup } from '@/components/ui/RadioGroup'

interface Props {
    group?: TemplateGroup
}

const TemplateField = ({ group }: Props) => {
    const templates = group?.templates ?? []

    if (!group || templates.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyDescription>
                        {group
                            ? 'This operating system has no versions available.'
                            : 'Choose an operating system first.'}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        )
    }

    return (
        <FormField
            name={'templateUuid'}
            render={({ field }) => (
                <FormItem>
                    <FormControl>
                        <RadioGroup
                            value={field.value}
                            onValueChange={field.onChange}
                            className={
                                'grid-cols-[repeat(auto-fill,minmax(14rem,1fr))]'
                            }
                        >
                            {templates.map(template => (
                                <TemplateCard
                                    key={template.uuid}
                                    template={template}
                                />
                            ))}
                        </RadioGroup>
                    </FormControl>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default TemplateField
