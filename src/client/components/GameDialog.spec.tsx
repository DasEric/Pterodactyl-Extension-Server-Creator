import { afterEach, expect, test, vi } from 'vitest';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { createExtensionTestHost } from '@pterodactyl/sdk/testing';
import GameDialog from './GameDialog';

afterEach(cleanup);

test('allows automatic port variables without increasing the port count first', async () => {
    const host = createExtensionTestHost({ extensionId: 'server-creator', prefix: 'srvc' });
    const save = vi.fn();
    render(<GameDialog game={null} eggs={[{ id: 1, name: 'Test egg', images: [], variables: [{ key: 'TELNET_PORT', name: 'Telnet Port' }] }]} pending={false} onClose={vi.fn()} onSave={save} />, { wrapper: host.Wrapper });
    fireEvent.change(await screen.findByLabelText('Name'), { target: { value: 'Test' } });
    const variables = screen.getByLabelText('Egg variables for extra ports') as HTMLInputElement;
    expect(variables.disabled).toBe(false);
    fireEvent.change(variables, { target: { value: 'TELNET_PORT' } });
    expect((screen.getByRole('button', { name: 'Save' }) as HTMLButtonElement).disabled).toBe(true);
    fireEvent.change(screen.getByLabelText('Extra ports'), { target: { value: '1' } });
    expect(screen.queryByLabelText('Port assignment')).toBeNull();
    expect(screen.queryByLabelText('Minimum total ports')).toBeNull();
    expect(screen.queryByLabelText('Add automatic port variable')).toBeNull();
    fireEvent.click(screen.getByRole('button', { name: 'Save' }));
    expect(save).toHaveBeenCalledWith(expect.objectContaining({ extra_ports: 1, ports: 2, port_mode: 'automatic', port_variables: ['TELNET_PORT'] }));
    fireEvent.change(variables, { target: { value: 'TYPO' } });
    expect(screen.getByRole('alert')).toBeTruthy();
    expect((screen.getByRole('button', { name: 'Save' }) as HTMLButtonElement).disabled).toBe(true);
    host.dispose();
});

test('uses only explicitly entered keys, including keys without PORT in their name', async () => {
    const host = createExtensionTestHost({ extensionId: 'server-creator', prefix: 'srvc' });
    const save = vi.fn();
    render(<GameDialog game={null} eggs={[{ id: 1, name: 'Custom egg', images: [], variables: [{ key: 'ADMIN_SOCKET', name: 'Remote console' }, { key: 'PORTAL_TITLE', name: 'Portal title' }, { key: 'RCON_PORT', name: 'RCON' }] }]} pending={false} onClose={vi.fn()} onSave={save} />, { wrapper: host.Wrapper });
    fireEvent.change(await screen.findByLabelText('Name'), { target: { value: 'Custom game' } });
    fireEvent.change(screen.getByLabelText('Extra ports'), { target: { value: '2' } });
    fireEvent.change(screen.getByLabelText('Egg variables for extra ports'), { target: { value: 'ADMIN_SOCKET, RCON_PORT' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save' }));
    expect(save).toHaveBeenCalledWith(expect.objectContaining({ extra_ports: 2, ports: 3, port_variables: ['ADMIN_SOCKET', 'RCON_PORT'] }));
    host.dispose();
});

test('allows zero extras and rejects duplicate or excess variables without changing the count', async () => {
    const host = createExtensionTestHost({ extensionId: 'server-creator', prefix: 'srvc' });
    const save = vi.fn();
    render(<GameDialog game={null} eggs={[{ id: 1, name: 'Test egg', images: [], variables: [{ key: 'TELNET_PORT', name: 'Telnet' }, { key: 'RCON_PORT', name: 'Rcon' }] }]} pending={false} onClose={vi.fn()} onSave={save} />, { wrapper: host.Wrapper });
    fireEvent.change(await screen.findByLabelText('Name'), { target: { value: 'Test' } });
    fireEvent.click(screen.getByRole('button', { name: 'Save' }));
    expect(save).toHaveBeenCalledWith(expect.objectContaining({ extra_ports: 0, ports: 1, port_variables: [] }));
    fireEvent.change(screen.getByLabelText('Extra ports'), { target: { value: '1' } });
    for (const value of ['TELNET_PORT, TELNET_PORT', 'TELNET_PORT, RCON_PORT']) {
        fireEvent.change(screen.getByLabelText('Egg variables for extra ports'), { target: { value } });
        expect((screen.getByRole('button', { name: 'Save' }) as HTMLButtonElement).disabled).toBe(true);
        expect((screen.getByLabelText('Extra ports') as HTMLInputElement).value).toBe('1');
    }
    host.dispose();
});
