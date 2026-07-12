import { cva } from 'class-variance-authority'

const labelVariants = cva(
    // Values from the create-page default (base + style "nova").
    'flex items-center gap-2 text-sm leading-none font-medium select-none group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50'
)

export default labelVariants
