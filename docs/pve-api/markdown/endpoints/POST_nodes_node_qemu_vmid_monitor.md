# POST /nodes/{node}/qemu/{vmid}/monitor

Execute QEMU monitor commands.

## Path parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| node | string | yes | The cluster node name. |
| vmid | integer | yes | The (unique) ID of the VM. |

## Request parameters

| Name | Type | Required | Description |
|---|---|---:|---|
| command | string | yes | The monitor command. |

## Returns

```json
{
  "type": "string"
}
```

## Permissions

```json
{
  "check": [
    "perm",
    "/vms/{vmid}",
    [
      "Sys.Audit",
      "Sys.Modify"
    ],
    "any",
    1
  ],
  "description": "The following commands do not require any additional privilege: ?, help, info\n\nThe following commands require 'Sys.Modify': announce_self, backup_cancel, balloon, block_job_cancel, block_job_complete, block_job_pause, block_job_resume, block_job_set_speed, block_resize, block_set_io_throttle, boot_set, c, calc_dirty_rate, cancel_vcpu_dirty_limit, chardev-send-break, closefd, commit, cont, cpu, delvm, eject, exit_preconfig, expire_password, getfd, gpa2hpa, gpa2hva, gva2gpa, i, loadvm, log, migrate_cancel, migrate_continue, migrate_pause, migrate_set_capability, migrate_set_parameter, migrate_start_postcopy, mouse_button, mouse_move, mouse_set, one-insn-per-tb, p, print, q, qemu-io, qom-get, qom-list, quit, replay_break, replay_delete_break, replay_seek, ringbuf_read, ringbuf_write, s, savevm, sendkey, set_link, set_password, set_vcpu_dirty_limit, snapshot_blkdev_internal, snapshot_delete_blkdev_internal, stop, stopcapture, sum, sync-profile, system_powerdown, system_reset, system_wakeup, trace-event, x, x_colo_lost_heartbeat, xp\n\nThe following commands are root-only: backup, block_stream, change, chardev-add, chardev-change, chardev-remove, client_migrate_info, device_add, device_del, drive_add, drive_backup, drive_del, drive_mirror, dump-guest-memory, dumpdtb, gdbserver, hostfwd_add, hostfwd_remove, logfile, mce, memsave, migrate, migrate_incoming, migrate_recover, nbd_server_add, nbd_server_remove, nbd_server_start, nbd_server_stop, netdev_add, netdev_del, nmi, o, object_add, object_del, pcie_aer_inject_error, pmemsave, qom-set, savevm-end, savevm-start, screendump, snapshot_blkdev, watchdog_action, wavcapture, xen-event-inject, xen-event-list\n\nThe following commands are deprecated: stopcapture, wavcapture\n"
}
```

## Raw schema

```json
{
  "allowtoken": 1,
  "description": "Execute QEMU monitor commands.",
  "method": "POST",
  "name": "monitor",
  "parameters": {
    "additionalProperties": 0,
    "properties": {
      "command": {
        "description": "The monitor command.",
        "type": "string",
        "typetext": "<string>"
      },
      "node": {
        "description": "The cluster node name.",
        "format": "pve-node",
        "type": "string",
        "typetext": "<string>"
      },
      "vmid": {
        "description": "The (unique) ID of the VM.",
        "format": "pve-vmid",
        "maximum": 999999999,
        "minimum": 100,
        "type": "integer",
        "typetext": "<integer> (100 - 999999999)"
      }
    }
  },
  "permissions": {
    "check": [
      "perm",
      "/vms/{vmid}",
      [
        "Sys.Audit",
        "Sys.Modify"
      ],
      "any",
      1
    ],
    "description": "The following commands do not require any additional privilege: ?, help, info\n\nThe following commands require 'Sys.Modify': announce_self, backup_cancel, balloon, block_job_cancel, block_job_complete, block_job_pause, block_job_resume, block_job_set_speed, block_resize, block_set_io_throttle, boot_set, c, calc_dirty_rate, cancel_vcpu_dirty_limit, chardev-send-break, closefd, commit, cont, cpu, delvm, eject, exit_preconfig, expire_password, getfd, gpa2hpa, gpa2hva, gva2gpa, i, loadvm, log, migrate_cancel, migrate_continue, migrate_pause, migrate_set_capability, migrate_set_parameter, migrate_start_postcopy, mouse_button, mouse_move, mouse_set, one-insn-per-tb, p, print, q, qemu-io, qom-get, qom-list, quit, replay_break, replay_delete_break, replay_seek, ringbuf_read, ringbuf_write, s, savevm, sendkey, set_link, set_password, set_vcpu_dirty_limit, snapshot_blkdev_internal, snapshot_delete_blkdev_internal, stop, stopcapture, sum, sync-profile, system_powerdown, system_reset, system_wakeup, trace-event, x, x_colo_lost_heartbeat, xp\n\nThe following commands are root-only: backup, block_stream, change, chardev-add, chardev-change, chardev-remove, client_migrate_info, device_add, device_del, drive_add, drive_backup, drive_del, drive_mirror, dump-guest-memory, dumpdtb, gdbserver, hostfwd_add, hostfwd_remove, logfile, mce, memsave, migrate, migrate_incoming, migrate_recover, nbd_server_add, nbd_server_remove, nbd_server_start, nbd_server_stop, netdev_add, netdev_del, nmi, o, object_add, object_del, pcie_aer_inject_error, pmemsave, qom-set, savevm-end, savevm-start, screendump, snapshot_blkdev, watchdog_action, wavcapture, xen-event-inject, xen-event-list\n\nThe following commands are deprecated: stopcapture, wavcapture\n"
  },
  "protected": 1,
  "proxyto": "node",
  "returns": {
    "type": "string"
  }
}
```
