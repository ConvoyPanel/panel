import TemplateGroupCard from '@/features/servers/components/client/Rebuild/TemplateGroupCard'
import { TemplateGroup } from '@/types/template-group.ts'

import {
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { RadioGroup } from '@/components/ui/RadioGroup'
import Skeleton from '@/components/ui/Skeleton'

interface Props {
    groups?: TemplateGroup[]
}

/**
 * `auto-fill` rather than a breakpoint: this renders both in the rebuild
 * page's capped column and inside the narrow card the deferred-install screen
 * puts it in, and the `@container` a `@md:` would measure is AppLayout's whole
 * content area, not the card (see docs/card-design.md).
 */
const TemplateGroupField = ({ groups }: Props) => (
    <FormField
        name={'templateGroupUuid'}
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
                        {groups
                            ? groups.map(group => (
                                  <TemplateGroupCard
                                      key={group.uuid}
                                      group={group}
                                  />
                              ))
                            : Array.from({ length: 4 }, (_, index) => (
                                  <Skeleton
                                      key={index}
                                      className={'h-[4.5rem]'}
                                  />
                              ))}
                    </RadioGroup>
                </FormControl>
                <FormMessage />
            </FormItem>
        )}
    />
)

export default TemplateGroupField
