import axios from 'axios'

// Not the shared `http` instance: its interceptors run the navigation progress
// bar for every request, and this one runs in the background on every page.
export default (page: string): Promise<any> =>
    axios.post(
        '/api/client/telemetry/views',
        { page },
        { headers: { Accept: 'application/json' }, withCredentials: true }
    )
