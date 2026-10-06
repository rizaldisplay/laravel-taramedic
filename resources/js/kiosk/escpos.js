/**
 * ESC/POS builder for 58mm / 80mm thermal queue tickets.
 * Layout optimized for RPP02 / Feasycom printers (bold + large ticket number).
 */

const encoder = new TextEncoder();

const PAPER_CHARS = {
    58: 32,
    80: 48,
};

const CMD = {
    INIT: [0x1b, 0x40],
    ALIGN_LEFT: [0x1b, 0x61, 0x00],
    ALIGN_CENTER: [0x1b, 0x61, 0x01],
    BOLD_ON: [0x1b, 0x45, 0x01],
    BOLD_OFF: [0x1b, 0x45, 0x00],
    /** ESC ! : double height + double width + bold-ish */
    SIZE_NORMAL: [0x1d, 0x21, 0x00],
    SIZE_DOUBLE: [0x1d, 0x21, 0x11], // 2x width, 2x height
    SIZE_LARGE: [0x1d, 0x21, 0x22], // 3x width, 3x height (if supported)
    FEED: [0x0a],
    CUT_PARTIAL: [0x1d, 0x56, 0x01],
    CUT_FULL: [0x1d, 0x56, 0x00],
};

const DEFAULT_RECEIPT_SETTINGS = {
    show_institution_name: true,
    show_institution_address: false,
    show_institution_phone: false,
    show_receipt_title: true,
    show_ticket_label: true,
    show_service_name: true,
    show_service_code: true,
    show_estimated_wait: true,
    show_waiting_count: true,
    show_queue_date: true,
    show_issued_time: true,
    show_footer: true,
    receipt_title_label: 'TIKET ANTRIAN',
    ticket_number_label: 'NOMOR ANTRIAN',
    service_name_label: 'Layanan',
    service_code_label: 'Kode',
    estimated_wait_label: 'Estimasi',
    waiting_count_label: 'Antrian',
    queue_date_label: 'Tanggal',
    issued_time_label: 'Jam',
    estimated_wait_suffix: 'menit',
    waiting_count_suffix: 'orang',
    footer_line_1: 'Harap menunggu panggilan',
    footer_line_2: 'di monitor ruang tunggu',
    thank_you_label: 'Terima kasih',
};

export async function encodeEscPosReceipt(ticket, options = {}) {
    const paperWidth = options.paperWidth === 80 ? 80 : 58;
    const chars = PAPER_CHARS[paperWidth];
    const institution = String(options.institutionName ?? '').trim() || 'Antrian';
    const institutionAddress = String(options.institutionAddress ?? '').trim();
    const institutionPhone = String(options.institutionPhone ?? '').trim();
    const receipt = { ...DEFAULT_RECEIPT_SETTINGS, ...(options.receiptSettings || {}) };
    const ticketNumber = String(ticket.ticket_number ?? '').trim();
    const serviceName = String(ticket.service_name ?? '').trim();
    const serviceCode = String(ticket.service_code ?? '').trim();
    const estimate = ticket.estimated_wait_minutes ?? 0;
    const waiting = ticket.waiting_count ?? 0;
    const date = String(ticket.queue_date ?? '').trim();
    const time = String(ticket.issued_at ?? '').trim();

    const out = [];
    const push = (...bytes) => {
        for (const part of bytes) {
            if (typeof part === 'number') {
                out.push(part);
            } else if (part instanceof Uint8Array || Array.isArray(part)) {
                out.push(...part);
            }
        }
    };
    const text = (value) => encoder.encode(sanitize(value));
    const line = (char = '-') => encoder.encode(char.repeat(chars));
    const nl = () => push(CMD.FEED);

    // Init
    push(CMD.INIT);
    push(0x1b, 0x74, 0x00); // code page

    // Header
    push(CMD.ALIGN_CENTER);
    if (truthy(receipt.show_institution_name)) {
        push(CMD.BOLD_ON);
        push(text(truncate(institution.toUpperCase(), chars)));
        nl();
        push(CMD.BOLD_OFF);
    }

    if (truthy(receipt.show_institution_address) && institutionAddress) {
        for (const addressLine of wrapText(institutionAddress, chars)) {
            push(text(addressLine));
            nl();
        }
    }

    if (truthy(receipt.show_institution_phone) && institutionPhone) {
        push(text(truncate(institutionPhone, chars)));
        nl();
    }

    if (truthy(receipt.show_receipt_title)) {
        push(text(truncate(label(receipt.receipt_title_label), chars)));
        nl();
    }

    push(line('='));
    nl();

    // Label
    if (truthy(receipt.show_ticket_label)) {
        push(text(truncate(label(receipt.ticket_number_label), chars)));
        nl();
        nl();
    }

    // Huge ticket number
    push(CMD.BOLD_ON);
    push(CMD.SIZE_LARGE);
    push(text(ticketNumber || '-'));
    nl();
    push(CMD.SIZE_NORMAL);
    push(CMD.BOLD_OFF);
    nl();

    push(line('='));
    nl();

    // Details (left)
    push(CMD.ALIGN_LEFT);
    if (truthy(receipt.show_service_name)) {
        push(text(row(label(receipt.service_name_label), serviceName || '-', chars)));
        nl();
    }

    if (truthy(receipt.show_service_code) && serviceCode) {
        push(text(row(label(receipt.service_code_label), serviceCode, chars)));
        nl();
    }

    if (truthy(receipt.show_estimated_wait)) {
        push(text(row(label(receipt.estimated_wait_label), `~${estimate} ${label(receipt.estimated_wait_suffix)}`.trim(), chars)));
        nl();
    }

    if (truthy(receipt.show_waiting_count)) {
        push(text(row(label(receipt.waiting_count_label), `${waiting} ${label(receipt.waiting_count_suffix)}`.trim(), chars)));
        nl();
    }

    if (truthy(receipt.show_queue_date) && date) {
        push(text(row(label(receipt.queue_date_label), date, chars)));
        nl();
    }

    if (truthy(receipt.show_issued_time) && time) {
        push(text(row(label(receipt.issued_time_label), `${time} WIB`, chars)));
        nl();
    }

    push(line('-'));
    nl();

    // Footer
    if (truthy(receipt.show_footer)) {
        push(CMD.ALIGN_CENTER);
        push(CMD.BOLD_ON);
        if (label(receipt.footer_line_1)) {
            push(text(truncate(label(receipt.footer_line_1), chars)));
            nl();
        }
        push(CMD.BOLD_OFF);
        if (label(receipt.footer_line_2)) {
            push(text(truncate(label(receipt.footer_line_2), chars)));
            nl();
        }
        if (label(receipt.thank_you_label)) {
            nl();
            push(text(truncate(label(receipt.thank_you_label), chars)));
            nl();
        }
    }

    nl();
    nl();
    nl();

    push(CMD.CUT_PARTIAL);

    return new Uint8Array(out);
}

function sanitize(value) {
    return String(value ?? '')
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\x20-\x7E\n]/g, '?');
}

function truncate(value, max) {
    const text = sanitize(value);

    return text.length > max ? text.slice(0, max) : text;
}

function row(label, value, width) {
    const left = sanitize(label);
    const right = sanitize(value);
    const separator = ' : ';
    const available = width - left.length - separator.length;

    if (available < 4) {
        return truncate(`${left}${separator}${right}`, width);
    }

    if (right.length <= available) {
        return `${left}${separator}${right}`;
    }

    // Wrap long service names onto next visual line by truncating smartly
    return `${left}${separator}${truncate(right, available)}`;
}

function wrapText(value, width) {
    const normalized = sanitize(value).replace(/\r\n?/g, '\n');
    const lines = [];

    for (const paragraph of normalized.split('\n')) {
        const words = paragraph.trim().split(/\s+/).filter(Boolean);

        if (words.length === 0) {
            lines.push('');

            continue;
        }

        let currentLine = '';

        for (const word of words) {
            if (word.length > width) {
                if (currentLine) {
                    lines.push(currentLine);
                    currentLine = '';
                }

                for (let index = 0; index < word.length; index += width) {
                    lines.push(word.slice(index, index + width));
                }

                continue;
            }

            const nextLine = currentLine ? `${currentLine} ${word}` : word;

            if (nextLine.length > width) {
                lines.push(currentLine);
                currentLine = word;
            } else {
                currentLine = nextLine;
            }
        }

        if (currentLine) {
            lines.push(currentLine);
        }
    }

    return lines.map((line) => truncate(line, width));
}

function label(value) {
    return String(value ?? '').trim();
}

function truthy(value) {
    return value === true || value === 1 || value === '1' || value === 'true';
}
