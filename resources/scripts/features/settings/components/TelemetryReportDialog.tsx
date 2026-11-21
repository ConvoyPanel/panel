import { useTelemetryReport } from '@/features/settings/api.ts'

import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogContent,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
} from '@/components/ui/ResponsiveDialog'
import Skeleton from '@/components/ui/Skeleton.tsx'

interface Props {
    open: boolean
    onOpenChange: (open: boolean) => void
}

const TelemetryReportDialog = ({ open, onOpenChange }: Props) => {
    const { data: report, isLoading, isError } = useTelemetryReport(open)

    return (
        <ResponsiveDialog open={open} onOpenChange={onOpenChange}>
            <ResponsiveDialogContent className={'sm:max-w-2xl'}>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle>
                        Telemetry report
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    {isLoading && <Skeleton className={'h-96'} />}
                    {isError && (
                        <p className={'text-muted-foreground text-sm'}>
                            Couldn't build the report.
                        </p>
                    )}
                    {report && (
                        <pre
                            className={
                                'bg-muted max-h-[60vh] overflow-auto rounded-md p-3 font-mono text-xs'
                            }
                        >
                            {JSON.stringify(report, null, 4)}
                        </pre>
                    )}
                </ResponsiveDialogBody>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default TelemetryReportDialog
