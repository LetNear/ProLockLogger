function updateDateTime() {
    const now = new Date();
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    let time = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')} ${ampm}`;

    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    let month = monthNames[now.getMonth()];
    let day = now.getDate().toString().padStart(2, '0');
    let year = now.getFullYear();
    let date = `${month} ${day}, ${year}`;

    document.getElementById('clock').innerText = time;
    document.getElementById('date').innerText = date;
    setTimeout(updateDateTime, 1000);
}
window.onload = updateDateTime;
