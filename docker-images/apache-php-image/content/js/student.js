$(function() {
        console.log("Loading Students");

    function loadStudents() {
        $.getJSON( "/api/students/", function( students ) {
            console.log(students[0]);
            var mess = "Nobody is here";
            if (students.length > 0) {
                mess = students[0].firstName + " " + students[0].lastname;
            }
            $(".java-test").text(mess);
        });
    };
    loadStudents();
    setInterval( loadStudents, 1000);
});