/**
 * يعيد بناء رأس الجدول ليتطابق ترتيبه مع buildColumns في tables/columns.js.
 * يبدأ بعمود التحكم، ثم التحديد إن وجد، ثم أعمدة show_list، ثم الإجراءات إن وجدت.
 * @param {object} data تعريف التبويب، ويحتوي show_list وhave_check_box وhave_actions.
 * @returns {void} يستبدل صفوف #table_id thead؛ أسماء الأعمدة تدخل عبر textContent حتى تعرض كنص.
 */
export function generateTableHeaders(data) {
    const row = document.createElement('tr');
    row.append(document.createElement('th'));
    if (data.have_check_box) {
        const cell = document.createElement('th');
        cell.innerHTML = '<div class="form-check form-check-md"><input class="form-check-input" id="master-check" type="checkbox"></div>';
        row.append(cell);
    }
    for (const column of data.show_list) {
        const cell = document.createElement('th');
        cell.textContent = column.title;
        row.append(cell);
    }
    if (data.have_actions) {
        const cell = document.createElement('th');
        cell.textContent = window.actionsText;
        row.append(cell);
    }
    document.querySelector('#table_id thead').replaceChildren(row);
}
