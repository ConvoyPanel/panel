import styled from '@emotion/styled'
import {
    Children,
    ReactElement,
    ReactNode,
    cloneElement,
    createContext,
} from 'react'
import tw from 'twin.macro'

interface EntityContextInterface {
    loading?: boolean
}

const EntityContext = createContext<EntityContextInterface>({})

interface Props {
    loading?: boolean
    children?: ReactElement | ReactElement[]
    actions?: ReactNode
}

const EntityContainer = styled.div`
    ${tw`flex flex-col sm:flex-row gap-4`}

    & > div:is(:first-of-type) {
        ${tw`border-t-0 pt-0`}
    }
`

const Entity = ({ loading, children: _children, actions }: Props) => {
    const childrenCount = Children.count(_children)
    const children = _children
        ? Children.map(_children, (child, idx) => {
              const isFirst = idx === 0
              const isLast = idx === childrenCount - 1

              return cloneElement(child, {
                  isFirst,
                  isLast,
                  actions: isFirst || isLast ? actions : undefined,
              })
          })
        : null

    return (
        <div className='relative p-4 bg-background border border-accent-200 rounded'>
            <EntityContext.Provider value={{ loading }}>
                <EntityContainer>{children}</EntityContainer>
            </EntityContext.Provider>
        </div>
    )
}

export default Entity
