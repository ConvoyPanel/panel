import { cva } from 'class-variance-authority'

const entityVariants = cva(
    'flex truncate px-3 py-2 leading-tight first:rounded-t-md last:rounded-b-md'
)

export default entityVariants
