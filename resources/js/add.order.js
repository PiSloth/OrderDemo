//JS file for add order and Supplier data
//Calculate Gram to MMunit KPY

function handleLivewireEvents(event){

    initFlowbite();
    console.log("listener");
    //weiht from add order blade
    const weight = document.getElementById("weight");
    //these two from supplier blade
    const weightInSupplier = document.getElementById("weightInSupplier");
    const youktwatInSupplier = document.getElementById("youktwatInSupplier");

    if (weight != null) {
        weight.addEventListener("keyup", function () {
            let weightValue = mmUnitCalc(weight.value);
            let gramToMmUnit = document.getElementById("gramToMmUnit");
            gramToMmUnit.innerText = weightValue;
            // gramToMmUnit.dispatchEvent(new Event("input"));
        });
    }
    if (weightInSupplier != null) {
        weightInSupplier.addEventListener("keyup", function () {
            // console.log("supplier");
            const weightValue = mmUnitCalc(weightInSupplier.value);

            const gramToMmUnitSupplier = document.getElementById(
                "gramToMmUnitSupplier"
            );
            gramToMmUnitSupplier.value = weightValue;
            document.getElementById("weightInnerText").innerText = weightValue;

            gramToMmUnitSupplier.dispatchEvent(new Event("input"));
        });
        youktwatInSupplier.addEventListener("keyup", function () {
            let weightValue = mmUnitCalc(youktwatInSupplier.value);
            let gramToMmUnit = document.getElementById(
                "gramToMmUnitYouktwatSupplier"
            );
            gramToMmUnit.value = weightValue;
            document.getElementById("youktwatInnerText").innerText =
                weightValue;

            gramToMmUnit.dispatchEvent(new Event("input"));
        });
    }

    //Gram to mmUnit Kyat Pae Yawe
    function mmUnitCalc(gramWeight) {
        let kyat = gramWeight * (1 / 16.606);
        kyat.toFixed(2);
        let answerKyat = Math.floor(kyat);

        let pae = (kyat - answerKyat) * 16;
        let answerPae = Math.floor(pae);

        let yawe = (pae - answerPae) * 8;
        let answerYawe = yawe.toFixed(2);
        if (answerKyat > 0) {
            return `${answerKyat} ကျပ် ${answerPae} ပဲ ${answerYawe} ရွေး`;
        } else if (answerPae > 0) {
            return ` ${answerPae} ပဲ ${answerYawe} ရွေး`;
        } else if (answerYawe > 0) {
            return `${answerYawe} ရွေး`;
        } else {
            return null;
        }
    }

    // flowbite dark mode by system theme
    if (
        localStorage.getItem("color-theme") === "dark" ||
        (!("color-theme" in localStorage) &&
            window.matchMedia("(prefers-color-scheme: dark)").matches)
    ) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }

}

document.addEventListener("livewire:navigated", handleLivewireEvents);
document.addEventListener("livewire:update", handleLivewireEvents);

