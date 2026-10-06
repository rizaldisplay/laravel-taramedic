@php
    $active = $active ?? false;
    $svgClass = $class ?? null;
@endphp
<svg @class($svgClass ?: ['h-6 w-6', $active ? 'menu-item-icon-active' : 'menu-item-icon-inactive']) width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 5.5C3.25 4.25736 4.25736 3.25 5.5 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V15.5C20.75 16.7426 19.7426 17.75 18.5 17.75H13.75V19.25H16C16.4142 19.25 16.75 19.5858 16.75 20C16.75 20.4142 16.4142 20.75 16 20.75H8C7.58579 20.75 7.25 20.4142 7.25 20C7.25 19.5858 7.58579 19.25 8 19.25H10.25V17.75H5.5C4.25736 17.75 3.25 16.7426 3.25 15.5V5.5ZM4.75 5.5V15.5C4.75 15.9142 5.08579 16.25 5.5 16.25H18.5C18.9142 16.25 19.25 15.9142 19.25 15.5V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H5.5C5.08579 4.75 4.75 5.08579 4.75 5.5Z" fill="currentColor"/>
</svg>
