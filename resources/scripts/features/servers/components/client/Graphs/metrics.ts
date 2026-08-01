import type { Server, ServerStateData, ServerTimepointData } from '@/types/server'
import byteSize from 'byte-size'

/**
 * The four metrics the resource panel plots, in the order they are stacked.
 *
 * Everything a metric needs -- its hue, its formatters, how to read it out of a
 * timepoint, how to read it out of live state -- lives here, so the panel, the
 * rail and the readout can never disagree about what "disk" means or what
 * colour it is.
 */
export type MetricKey = 'cpu' | 'memory' | 'disk' | 'network'

/** A metric plotted as one series against a baseline of zero. */
interface SingleSeriesShape {
    kind: 'single'
    /** Label for the one series; also the legend text where one is shown. */
    label: string
}

/**
 * A metric whose two series are opposed directions of the same thing. They
 * share the metric's hue and split across a mirrored baseline -- `positive`
 * above it, `negative` below -- because two steps of one hue cannot be told
 * apart reliably, and position is an encoding colour blindness cannot take.
 */
interface MirroredShape {
    kind: 'mirrored'
    positive: string
    negative: string
}

export interface Metric {
    key: MetricKey
    name: string
    /** CSS custom property carrying this metric's hue in both themes. */
    color: string
    shape: SingleSeriesShape | MirroredShape
    /** Axis ticks and other cramped spots. */
    formatShort: (value: number) => string
    /** Readouts and tooltips, where there is room to be precise. */
    format: (value: number) => string
    /**
     * Fixed axis maximum, when the metric has one. CPU is always 0-100% and
     * memory is always 0-limit; a disk or network axis has to follow the data.
     */
    ceiling?: (server: Server | undefined) => number | undefined
    /** The values to plot, pulled from one historical timepoint. */
    fromTimepoint: (point: ServerTimepointData) => number[]
    /** The values to plot, pulled from live guest state (absent where live state carries no such reading). */
    fromState?: (state: ServerStateData) => number[]
    /** Caption under the rail readout. */
    caption: (server: Server | undefined) => string
}

const bytes = (value: number, precision = 1) =>
    byteSize(Math.abs(value), { units: 'iec', precision }).toString()

const bytesShort = (value: number) => bytes(value, 0)
const rate = (value: number) => `${bytes(value, 1)}/s`
const rateShort = (value: number) => `${bytes(value, 0)}/s`
const percent = (value: number) => `${Math.round(Math.abs(value))}%`

export const METRICS: Metric[] = [
    {
        key: 'cpu',
        name: 'CPU',
        color: 'var(--chart-cpu)',
        shape: { kind: 'single', label: 'Usage' },
        format: percent,
        formatShort: percent,
        ceiling: () => 100,
        fromTimepoint: point => [point.cpuUsed * 100],
        fromState: state => [state.cpuUsed * 100],
        caption: server =>
            server ? `${server.cpu} ${server.cpu === 1 ? 'vCPU' : 'vCPU'}` : '',
    },
    {
        key: 'memory',
        name: 'Memory',
        color: 'var(--chart-memory)',
        shape: { kind: 'single', label: 'Used' },
        format: value => bytes(value, 2),
        formatShort: bytesShort,
        ceiling: server => server?.memory,
        fromTimepoint: point => [point.memoryUsed],
        fromState: state => [state.memoryUsed],
        caption: server => (server ? `of ${bytes(server.memory, 0)}` : ''),
    },
    {
        key: 'disk',
        name: 'Disk I/O',
        color: 'var(--chart-disk)',
        shape: { kind: 'mirrored', positive: 'Read', negative: 'Write' },
        format: rate,
        formatShort: rateShort,
        fromTimepoint: point => [point.disk.read, point.disk.write],
        caption: () => 'read / write',
    },
    {
        key: 'network',
        name: 'Network',
        color: 'var(--chart-network)',
        shape: { kind: 'mirrored', positive: 'In', negative: 'Out' },
        format: rate,
        formatShort: rateShort,
        fromTimepoint: point => [point.network.in, point.network.out],
        caption: () => 'in / out',
    },
]

/** The series keys a metric contributes to a chart row's data points. */
export const seriesKeys = (metric: Metric): [string] | [string, string] =>
    metric.shape.kind === 'single'
        ? [metric.key]
        : [`${metric.key}Positive`, `${metric.key}Negative`]

/** Human labels for a metric's series, in the same order as `seriesKeys`. */
export const seriesLabels = (metric: Metric): string[] =>
    metric.shape.kind === 'single'
        ? [metric.shape.label]
        : [metric.shape.positive, metric.shape.negative]
