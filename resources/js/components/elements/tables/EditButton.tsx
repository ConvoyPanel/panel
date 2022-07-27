import RoundedButton from '@/components/RoundedButton'
import { PencilIcon } from '@heroicons/react/solid'

interface Props {
    onClick?: () => void
}
const EditButton = ({ onClick }: Props) => {
  return (
    <RoundedButton onClick={() => onClick && onClick()}>
      <PencilIcon className='text-gray-600 hover:text-blue-600 w-[18px] h-[18px]' />
    </RoundedButton>
  )
}

export default EditButton
