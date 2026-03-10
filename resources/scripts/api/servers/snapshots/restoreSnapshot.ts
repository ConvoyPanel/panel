import axios from '@/lib/axios';

const restoreSnapshot = async (uuid: string, snapshotUuid: string): Promise<void> => {
    await axios.post(`/api/client/servers/${uuid}/snapshots/${snapshotUuid}/restore`);
};

export default restoreSnapshot;
