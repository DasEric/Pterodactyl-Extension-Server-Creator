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
    const variables = screen.getByLabelText('Variables for the extra ports') as HTMLInputElement;
    expect(variables.disabled).toBe(false);
    fireEvent.change(variables, { target: { value: 'TELNET_PORT' } });
    expect(screen.getByText(/2 ports required/)).toBeTruthy();
    fireEvent.click(screen.getByRole('button', { name: 'Save' }));
    expect(save).toHaveBeenCalledWith(expect.objectContaining({ port_mode: 'automatic', port_variables: ['TELNET_PORT'] }));
    fireEvent.change(variables, { target: { value: 'TYPO' } });
    expect(screen.getByRole('alert')).toBeTruthy();
    expect((screen.getByRole('button', { name: 'Save' }) as HTMLButtonElement).disabled).toBe(true);
    host.dispose();
});
