function viewproduct(){
    var view= document.getElementById("view");
    view.style.flex=2;
}

function closeview(){
    var view= document.getElementById("view");
    view.style.flex=0;
}

document.addEventListener('DOMContentLoaded', () => {
    var blocks= document.getElementsByClassName("block");
    for(var i=0;i<blocks.length;i++){
        blocks[i].addEventListener("click",viewproduct);
    }
    var view= document.getElementById("view");
    view.addEventListener('click',closeview)
});