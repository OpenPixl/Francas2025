import axios from 'axios';

export function filters() {
    document.querySelectorAll('#filters input').forEach(input => {
        input.addEventListener('change', () => {
            const FiltersForms = document.getElementById('filters');
            console.log(FiltersForms);
            // J'intercepte les clics et ses données.
            const form = new FormData(FiltersForms);
            const action = '/webapp/ressources/filter'
            // construction de la "QueryString"
            const Params = new URLSearchParams();
            // Alimentation de la "QueryString"
            form.forEach((value,key) => {
                Params.append(key, value);
            })
            axios
                .get(action + "?" + Params.toString())
                .then(response => {
                    // rafraichissement du tableau
                    const liste = document.getElementById('liste').innerHTML = response.data.liste;
                })
        });
    });
}
