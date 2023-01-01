import Button from '@/components/elements/Button'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'

const DeleteNodeContainer = () => {
    return (
        <FormSection title='Danger Zone' description='ayo. be careful'>
            <FormCard className='w-full border-error'>
                <FormCard.Body>
                    <FormCard.Title>Delete Node</FormCard.Title>
                    <div className='space-y-3 mt-3'>
                        <FlashMessageRender byKey='admin:node:settings:delete' />
                    </div>
                </FormCard.Body>
                <FormCard.Footer>
                    <Button
                        type='submit'
                        variant='filled'
                        color='danger'
                        size='sm'
                    >
                        Delete
                    </Button>
                </FormCard.Footer>
            </FormCard>
        </FormSection>
    )
}

export default DeleteNodeContainer
