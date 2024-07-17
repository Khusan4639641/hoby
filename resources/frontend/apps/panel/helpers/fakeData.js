function rndIdx(arrLength){ return Math.floor(Math.random() * arrLength)}
function rndPhone(){ return Math.floor(Math.random() * 1000000000)}
function rndIn(num = 120){ return Math.floor(Math.random() * num)}

const names = ["Шахзод Машраббоев Абдуллаевич","Alisher Akbarov","Anvar Alimov","Sarvar Mikhaylov"]
const distrcits = [ "Яккасарайский р-он", "Мирабадский р-он", "Янгихаетский р-он", "Юнусабадский р-он"]
const debt_collect_sum = [115000000, 135000000, 155000000, 175000000]

// export const debtors = [
//     { id: 1, full_name: "Шахзод Машраббоев Абдуллаевич", phone: "998900430457", region: "г. Ташкент", distrcit: "Яккасарайский р-он",expired_days: 12, debt_collect_sum: 115000000.14},
//     { id: 2, full_name: "Alisher Akbarov", phone: "998900667757", region: "г. Ташкент", distrcit: "Мирабадский р-он",expired_days: 110, debt_collect_sum: 135000000.14},
//     { id: 3, full_name: "Anvar Alimov", phone: "998903355457", region: "г. Ташкент", distrcit: "Янгихаетский р-он",expired_days: 60, debt_collect_sum: 155000000.14},
//     { id: 4, full_name: "Sarvar Mikhaylov", phone: "998900523457", region: "г. Ташкент", distrcit: "Юнусабадский р-он",expired_days: 90, debt_collect_sum: 175000000.14},
// ]
export function fakeDebtors(dataCount=30){
    let dataArr = []
    for (let i = 0; i < dataCount; i++) {
        const element = { 
            id: i+1, 
            full_name: names[rndIdx(4)], 
            phone: `998${rndPhone()}`, 
            region: "г. Ташкент", 
            distrcit: distrcits[rndIdx(4)],
            expired_days: rndIn(300), 
            debt_collect_sum: debt_collect_sum[rndIdx(4)]
        };
        dataArr.push(element)
    }

    return dataArr
} 