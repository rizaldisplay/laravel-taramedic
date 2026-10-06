import assert from 'node:assert/strict';
import test from 'node:test';
import {
    formatTicketNumberForSpeech,
    spokenTicketCharacters,
} from '../../resources/js/tts/ticket-pronunciation.js';

test('menyebutkan seluruh angka nol ketika pengaturan aktif', () => {
    assert.deepEqual(spokenTicketCharacters('A001', true), ['A', '0', '0', '1']);
    assert.equal(formatTicketNumberForSpeech('A001', true), 'a, kosong, kosong, satu');
});

test('melewati setiap angka nol ketika pengaturan dimatikan', () => {
    assert.deepEqual(spokenTicketCharacters('A001', false), ['A', '1']);
    assert.deepEqual(spokenTicketCharacters('A101', false), ['A', '1', '1']);
    assert.deepEqual(spokenTicketCharacters('A000', false), ['A']);
    assert.deepEqual(spokenTicketCharacters('000', false), []);
    assert.deepEqual(spokenTicketCharacters('A123', false), ['A', '1', '2', '3']);
    assert.equal(formatTicketNumberForSpeech('A001', false), 'a, satu');
});
