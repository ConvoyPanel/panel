import axios from '@/lib/axios';

const restoreSnapshot = async (uuid: string, snapshotId: number): Promise<void> => {
    await axios.post(`/api/client/servers/${uuid}/snapshots/${snapshotId}/restore`);
};

export default restoreSnapshot;
