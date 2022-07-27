import { Group, Text } from '@mantine/core'
import { forwardRef } from 'react'

export interface ItemProps extends React.ComponentPropsWithoutRef<'div'> {
  label: string
  description: string
}

const SelectItem = forwardRef<HTMLDivElement, ItemProps>(
  ({ label, description, ...others }: ItemProps, ref) => (
    <div ref={ref} {...others}>
      <Group noWrap>
        <div>
          <Text size='sm'>{label}</Text>
          <Text size='xs' color='dimmed'>
            {description}
          </Text>
        </div>
      </Group>
    </div>
  )
)

export default SelectItem
