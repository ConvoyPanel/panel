import { useMailSettings } from '@/features/settings/api.ts'
import { IconAlertTriangle } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

import { Alert, AlertDescription } from '@/components/ui/Alert'

interface Props {
    /** What this screen loses without mail. One short sentence; the component supplies the rest. */
    children: string
}

/**
 * Warns that mail is not set up, on the screen where that costs someone something.
 *
 * Deliberately not on the mail settings screen itself. Nobody is harmed by unconfigured mail
 * while they are looking at the form that configures it — an empty host already says so, and
 * the warning there would just restate the field below it. The place it earns its weight is
 * where an action silently does less than the operator expects, so the consequence is passed
 * in rather than baked in: this component knows mail is off, and the caller knows what that
 * means for the thing it is about to do.
 *
 * Renders nothing while the query is in flight, so a dialog does not visibly reflow around a
 * warning that then turns out not to apply.
 */
const MailNotConfiguredAlert = ({ children }: Props) => {
    const { data: settings } = useMailSettings()

    if (!settings || settings.configured) return null

    return (
        <Alert>
            <IconAlertTriangle className={'size-4'} />
            <AlertDescription>
                Mail isn't set up. {children}{' '}
                <Link
                    to={'/admin/settings/mail'}
                    className={'underline underline-offset-4'}
                >
                    Set up mail
                </Link>
                .
            </AlertDescription>
        </Alert>
    )
}

export default MailNotConfiguredAlert
