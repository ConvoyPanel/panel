# Writing components

## Where to create your components

- For components that declare the general structure and layout of a page:
    - `/components/layouts/xxx`
- For components that are tightly coupled to a specific interface:
    - `/components/interfaces/xxx`
- For components that are meant to be reusable across multiple pages:
    - `/components/ui/xxx`

## Template for building components

### If props are exported

```ts

// Declare the prop types of your component
export interface ComponentAProps {
    sampleProp: string
}

// Name your component accordingly
const ComponentA = ({ sampleProp }: ComponentAProps) => {
    return <div>ComponentA
:
    {
        sampleProp
    }
    </div>
}

export default ComponentA
```

### If props are not exported

```ts
interface Props {
    sampleProp: string
}

// Name your component accordingly
const ComponentB = ({ sampleProp }: Props) => {
    return <div>ComponentB
:
    {
        sampleProp
    }
    </div>
}

export default ComponentB
```
