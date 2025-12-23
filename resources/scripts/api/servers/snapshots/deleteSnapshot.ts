import axios from '@/lib/axios';

const deleteSnapshot = async (uuid: string, snapshotId: number): Promise<void> => {
    await axios.delete(`/api/client/servers/${uuid}/snapshots/${snapshotId}`);
};

export default deleteSnapshot;
