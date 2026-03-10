import axios from '@/lib/axios';

const deleteSnapshot = async (uuid: string, snapshotUuid: string): Promise<void> => {
    await axios.delete(`/api/client/servers/${uuid}/snapshots/${snapshotUuid}`);
};

export default deleteSnapshot;
