import axios from '@/lib/axios';

interface CreateSnapshotData {
    name: string;
    description?: string;
    includes_ram: boolean;
}

const createSnapshot = async (uuid: string, data: CreateSnapshotData): Promise<void> => {
    await axios.post(`/api/client/servers/${uuid}/snapshots`, data);
};

export default createSnapshot;
