import FormCard from '@/components/elements/FormCard'
import FormSection from '@/components/elements/FormSection'
import { FormikProvider, useFormik } from 'formik'
import { ServerContext } from '@/state/server'
import { AdminServerContext } from '@/state/admin/server'
import FlashMessageRender from '@/components/elements/FlashMessageRenderer'
import UsersSelectForm from '@/components/admin/servers/UsersSelectForm'
import NodesSelectForm from '@/components/admin/servers/NodesSelectForm'
import TextInputFormik from '@/components/elements/formik/TextInputFormik'
import SelectFormik from '@/components/elements/formik/SelectFormik'
import Button from '@/components/elements/Button'
import { useFlashKey } from '@/util/useFlash'
import updateServer from '@/api/admin/servers/updateServer'
import { EloquentStatus } from '@/api/server/types'
import ServerInformationContainer from '@/components/admin/servers/settings/ServerInformationContainer'
import SuspensionContainer from '@/components/admin/servers/settings/SuspensionContainer'
import DeleteServerContainer from '@/components/admin/servers/settings/DeleteServerContainer'

const GeneralContainer = () => {
    return (
        <>
            <ServerInformationContainer />
            <SuspensionContainer />

            <DeleteServerContainer />
        </>
    )
}

export default GeneralContainer
