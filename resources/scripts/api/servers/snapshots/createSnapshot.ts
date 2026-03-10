import axios from '@/lib/axios';

interface CreateSnapshotData {
    name: string;
    description?: string;
    includesRam: boolean;
}

const createSnapshot = async (uuid: string, data: CreateSnapshotData): Promise<void> => {
    await axios.post(`/api/client/servers/${uuid}/snapshots`, {
        name: data.name,
        description: data.description,
        includes_ram: data.includesRam,
    });
};

export default createSnapshot;
