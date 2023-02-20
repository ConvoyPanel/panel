import { createContext } from 'react'

const EntityContext = createContext<Props>({})

interface Props {
    loading?: boolean
}

const Entity = ({ loading }: Props) => {
    return (
        <div className='relative p-4 bg-background border border-accent-200 rounded'>
            <EntityContext.Provider value={{ loading }}>
                <div className='flex flex-col lg:flex-row gap-4'>Entity</div>
            </EntityContext.Provider>
        </div>
    )
}

export default Entity
