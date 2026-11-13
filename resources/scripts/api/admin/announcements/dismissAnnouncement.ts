import http from '@/api/http'

const dismissAnnouncement = (id: string) =>
    http.post(`/api/admin/announcements/${encodeURIComponent(id)}/dismiss`)

export default dismissAnnouncement
