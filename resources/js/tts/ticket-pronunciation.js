export const indonesianDigits = {
    0: 'kosong',
    1: 'satu',
    2: 'dua',
    3: 'tiga',
    4: 'empat',
    5: 'lima',
    6: 'enam',
    7: 'tujuh',
    8: 'delapan',
    9: 'sembilan',
};

export const indonesianLetters = {
    A: 'a', B: 'b', C: 'c', D: 'd', E: 'e', F: 'f', G: 'g',
    H: 'h', I: 'i', J: 'j', K: 'k', L: 'l', M: 'm', N: 'n',
    O: 'o', P: 'p', Q: 'q', R: 'r', S: 's', T: 't', U: 'u',
    V: 'v', W: 'w', X: 'x', Y: 'y', Z: 'z',
};

export const spokenTicketCharacters = (ticketNumber, speakZeroDigits = true) => String(ticketNumber ?? '')
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')
    .split('')
    .filter((character) => speakZeroDigits || character !== '0');

export const formatTicketNumberForSpeech = (ticketNumber, speakZeroDigits = true) => spokenTicketCharacters(
    ticketNumber,
    speakZeroDigits,
)
    .map((character) => indonesianDigits[character] ?? indonesianLetters[character] ?? character)
    .join(', ');
