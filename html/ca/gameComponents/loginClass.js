window.login=(function(){
    
    var username = null;
    var userAvatarCache = [];
    var isLoggedIn = false;
    /**
     * Checks if the console is open
     * @author Ignacy Debicki
     * @returns {boolean} If console is open
     */
    function inspectIsOpen()
    {
        console.profile(); 
        console.profileEnd(); 
        if (console.clear) console.clear();
        return console.profiles.length > 0;
    }
    /**
     * Fetches the avatar for the suer
     * @author Ignacy Debicki
     * @param {string}   user     Username of user to fetch avatar for
     * @param {function} callback Function called upon completion. Will be passed [fileURL,user]
     */
    function fetchAvatar(user,callback){
        if (user==""){
            if(userAvatarCache[username]){
                if(userAvatarCache[username].hasOwnProperty("file")){
                    if (userAvatarCache[username].expiry>Date.now() && userAvatarCache[username].file){
                        callback(userAvatarCache[username].file,username);
                        return;
                    }
                }
            }
        }
        if(userAvatarCache[user]){
            if(userAvatarCache[user].hasOwnProperty("file")){
                    if (userAvatarCache[user].expiry>Date.now() && userAvatarCache[user].file){
                        callback(userAvatarCache[user].file,user);
                        return;
                    }
            }
        }
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"fetchAvatar", "json":'{"user":"'+user+'"}'},
            error:logError,
            success:function(response){
                var file = null;
                if (response["success"]==true){
                    var file = response["data"];
                }else{
                    alert(response["error"],response["errorCode"]);   
                } 
                if (file == null || file == ""){
                    file = "defaultAvatar.png";
                }
                file = "restricted/images/avatars/" + file;
                var userAvatar = {"file":file,"expiry":Date.now()+10000*60} //check again in ten minutes
                //cache
                if (user==""){
                    user=username;
                }
                userAvatarCache[user]=userAvatar;
                callback(userAvatar.file,user);
            }
        });
    }
    /**
     * Logs the user in
     * @author Ignacy Debicki
     * @param {string}   user     Username to use
     * @param {string}   pass     Password to use
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function login(user,pass,callback){
        $.ajax({
            url: 'restricted/login.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"type":"login", "user":user, "password":pass},
            success:function(response){
                if (response["success"]==true){
                    isLoggedIn = true;
                    username = response["username"]
                    if (!(callback === undefined)){
                        callback(true);   
                    }
                }else{
                    isLoggedIn = false;
                    if (!(callback === undefined)){
                        callback(false,response["error"],response["errorCode"]);   
                    }
                }
            },
            error:logError
        });
    }
    /**
     * Logs the current user out of the session
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function logout(callback){
        $.ajax({
            url: 'restricted/logout.php',
            dataType: "json",
            success: function(response){ 
                if (response["success"]==true){
                    isLoggedIn = false;
                }
                if (!(callback === undefined)){
                    callback(response["success"],response["error"],response["errorCode"]);   
                }
            },
            error:logError
        });
    }
    /**
     * Creates a new user account
     * @author Ignacy Debicki
     * @param {string}   user     Username to use
     * @param {string}   pass     Password to use
     * @param {string}   passR    Repeat of password for validaiton purposes
     * @param {string}   email    Email to use for registration
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function createAccount(user,pass,passR,email,callback){
        if (validateAccountCreationDetails(user,pass,passR,email)){
            $.ajax({
                url: 'restricted/login.php',
                type: 'POST',
                method: 'POST',
                dataType: "json",
                data: { "type": "create", "user":user, "password":pass, "passwordR":passR, "email":email},
                success: function(response){ 
                    if (response["success"]==true){
                        if (!(callback === undefined)){
                            callback(true);   
                        }
                    }else{
                        if (!(callback === undefined)){
                            callback(false,response["error"],response["errorCode"]);   
                        }
                    }
                },
                error:logError
            });
        }
    }
    /**
     * Validates the account details for acount creation
     * @author Ignacy Debicki
     * @param   {string}  username  Username to be used
     * @param   {string}  password  Password to be used
     * @param   {string}  passwordR Repeat of password to be used
     * @param   {string}  email     Email to be used
     * @returns {boolean} If valid
     */
    function validateAccountCreationDetails(username,password,passwordR,email){
        //check that username does not breach rules
        if (validateUser(username)==false){
            return false;   
        }
        //check that password does not breach rules
        if (validatePassword(password)==false || validatePassword(passwordR)==false || password != passwordR){
            return false;
        }
        //check email does not breach rules
        if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(email)==false){
            return false;
        }
        return true;
    }
    /**
     * Validates an account username
     * @author Ignacy Debicki
     * @param   {string}  username Username to validate
     * @returns {boolean} If valid
     */
    function validateUser(username){
        if (username == "" || /^[a-zA-Z0-9]+$/.test(username)==false){
            return false;
        }
        return true;
    }
    /**
     * Validates password
     * @author Ignacy Debicki
     * @param   {string}  password Password to validate
     * @returns {boolean} If valid
     */
    function validatePassword(password){
        if(/\s/g.test(password)||password==""){
            return false;   
        }
        return true;
    }
    /**
     * Fetches notifications from server
     * @author Ignacy Debicki
     * @param {number}   timestamp Timestamp to fetch notifications up to. (pass 0 for now())
     * @param {number}   limit     Maximum amount of rows to return (pass 0 for all)
     * @param {function} callback  Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function fetchNotifications(timestamp,limit,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "fetchNotifications", "json":'{"fromDate":' + timestamp +',"limit":' + limit +'}'},
            success: function(response){ 
                if (response["success"]==true){
                    var responseData = response["data"];
                    callback(true,responseData);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Marks a notification as read
     * @author Ignacy Debicki
     * @param {Array}    msgIds   Array of msgIds to mark as read
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function markAsRead(msgIds,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "markAsRead", "json":'{"msgIds":'+ JSON.stringify(msgIds)+'}'},
            success: function(response){
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Uploads and changes the current user's avatar
     * @author Ignacy Debicki
     * @param {object}   formdata Formdata from avatar upload form
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function uploadAvatar(formdata,callback){
        $.ajax({
            url: "restricted/performAction.php",
            method: "POST",
            data: formdata,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                if(response["success"]==true){
                    //need to set new userAvatar
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            },
            error:logError
        });
    }
    /**
     * Deletes the current user's avatar
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function deleteAvatar(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"removeAvatar", "json":"{}"},
            success:function(response){
                if(response["success"]==true){
                    //need to set new userAvatar
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            },
            error:logError
        }); 
    }
    /**
     * Deletes the current user's account
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function deleteAccount(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: {"action":"deleteAccount", "json":"{}"},
            success:function(response){
                if (response["success"]==true){
                    isLoggedIn=false;
                    username=null;
                    userAvatar=null;
                }
                callback(response["success"],response["error"],response["errorCode"]);
            },
            error:logError
        });  
    }
    /**
     * Fetches the user's friends
     * @author Ignacy Debicki
     * @param {string}   user     Username of user to fetch for
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getFriends(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getFriends", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    var responseData = response["data"];
                    callback(true,responseData);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Accepts a friend request
     * @author Ignacy Debicki
     * @param {string}   userFrom Who the friend request was from
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function acceptFriendRequest(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "acceptFriendRequest", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Refuse a friend request
     * @author Ignacy Debicki
     * @param {string}   userFrom Who the friend request was from
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function refuseFriendRequest(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "refuseFriendRequest", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Delete a sent friend request
     * @author Ignacy Debicki
     * @param {string}   userTo   Who the request was sent to
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function deleteFriendRequest(userTo,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "deleteFriendRequest", "json":'{"userTo":"' + userTo +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Send friend request to user
     * @author Ignacy Debicki
     * @param {string}   userTo   User to send request to
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function sendFriendRequest(userTo,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "createFriendRequest", "json":'{"userTo":"' + userTo +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Block a user
     * @author Ignacy Debicki
     * @param {string}   user     User to block
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function blockUser(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "blockUser", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Marks a friend request as read
     * @author Ignacy Debicki
     * @param {string}   userFrom Who the request was from
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function markFRAsRead(userFrom,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "markFRAsRead", "json":'{"userFrom":"' + userFrom +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Unblocks a user
     * @author Ignacy Debicki
     * @param {string}   user     User to unblock
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function unblockUser(user,callback){
         $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "unblockUser", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Deletes a friend
     * @author Ignacy Debicki
     * @param {string}   user     User to delete
     * @param {function} callback Function to be executed upon completion. Will be passed [success,errorMessage,errorCode]
     */
    function deleteFriend(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "deleteFriend", "json":'{"user":"' + user +'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Gets friend requests
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getFriendRequests(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getFriendRequests", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Gets sent friend requests
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getSentFriendRequests(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getSentFriendRequests", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Gets blocked users
     * @author Ignacy Debicki
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getBlockedUsers(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getBlockedUsers", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Makes a precise search on the needle in the user database
     * @author Ignacy Debicki
     * @param {string}   needle   Search string
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getExactUsers(needle,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getExactUsers", "json":'{"needle":"'+needle+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Rough search (contains) in user database
     * @author Ignacy Debicki
     * @param {string}   needle   Search string
     * @param {function} callback Function to be executed upon completion. Will be passed [success,responseData (if success==false, error mesage will be passed here),errorCode]
     */
    function getAllUsers(needle,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getAllUsers", "json":'{"needle":"'+needle+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Attempts to set the score as a new highscore
     * @author Ignacy Debicki
     * @param {number}   score    The score to try to set
     * @param {number}   map      Id of map the score was achieved on
     * @param {function} callback Function to call when completed.
     Requires parameters [success,data,errorCode]. Data will be either:
     error information (if success==false)
     or
     new rank of user for that map (if success==true)
     */
    function setScore(score,map,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "addScore", "json":'{"map":"'+map+'","score":'+score+' }'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Returns if the score is a new highscore for the user on that map
     * @author Ignacy Debicki
     * @param {string}   user     Username of user to check for
     * @param {number}   score    Score to check
     * @param {number}   map      Id of map to check for
     * @param {function} callback Callback which will be called when  function completes.
     Requires parameters [success,data,errorCode]. Data will be either:
     error information (if success==false)
     or
     0 if not highscore,
      1 if new highscore over previous score,
      2 if new highscore and no previous score on the map
    (if success==true)
     */
    function isHighScore(user,score,map,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "isHighScore", "json":'{"map":"'+map+'","score":'+score+',"user":"'+user+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Returns the rank of the user on the map in the timespan
     * @author Ignacy Debicki
     * @param {string} user       Username of user to check for
     * @param {number} map        Id of map to check for
     * @param {object} timespan   Dictionary containing from and to timespan.
     Format ["from" : from, "to" : to].
     Set "to" = 0 if current date is desired. Both ranges are inclusive
     * @param {function} callback Function to call once the function completes.
     Requires parameters [success,data,errorCode].
     Data will be either:
     error information (if success==false)
     or
     rank of user for that map (if success==true)
     0 if user does not have score recordeed for map
     */
    function userRank(user,map,timespan,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "userRank", "json":'{"map":"'+map+'","timespan":'+timespan+',"user":"'+user+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Gets all of the highscores and all time rank for each map for a specific user
     * @author Ignacy Debicki
     * @param {string}   user     Username of user for whom to get highscores
     * @param {function} callback Fucntion caled after completion.
     Requires parameters [success,data,errorCode].
     Data will be either:
     error information (if success==false)
     or
     Dictionary of format:
     {mapId : {"score" : score, "rank" : rank, "timestamp" : "timestamp"}} (if success==true)
     */
    function getUserHighScores(user,callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getUserHighScores", "json":'{"user":"'+user+'"}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    /**
     * Gets all the maps available ordered by their levelNo
     * @author Ignacy Debicki
     * @param {function} callback Function to call once completed. 
     Requires parameters [success,data,errorCode].
     Data will be either:
     error information (if success==false)
     or
     Array of format:
     {{"id"=>id,"name"=>name,"description"=>description,"file"=>file,"image"=>image}} (if success==true)
     */
    function getAllMaps(callback){
        $.ajax({
            url: 'restricted/performAction.php',
            type: 'POST',
            method: 'POST',
            dataType: "json",
            data: { "action": "getMapList", "json":'{}'},
            success: function(response){ 
                if (response["success"]==true){
                    callback(true,response["data"]);
                }else{
                    callback(false,response["error"],response["errorCode"]); 
                }
            },
            error:logError
        });
    }
    
    return{
        login:function(user,pass,callback){
            login(user,pass,callback);
        },
        checkCookie: function(callback){
            login("","",callback);
        },
        userAvatar: function(callback){
            fetchAvatar("",callback);
        },
        username: function(){
            return username;
        },
        logout: function(callback){
            logout(callback);
        },
        createAccount(user,pass,passR,email,callback){
            createAccount(user,pass,passR,email,callback);
        },
        isLoggedIn: function(){
            return isLoggedIn;
        },
        fetchNotifications: function(timestamp,limit,callback){
            fetchNotifications(timestamp,limit,callback);
        },
        markAsRead: function(msgIds,callback){
            markAsRead(msgIds,callback);
        },
        uploadAvatar: function(formData,callback){
            uploadAvatar(formData,callback);
        },
        deleteAvatar: function(callback){
            deleteAvatar(callback);
        },
        deleteAccount: function(callback){
            deleteAccount(callback);
        },
        fetchAvatar: function(user,callback){
            fetchAvatar(user,callback);
        },
        getUserFriends: function(callback){
            return getFriends("",callback);
        },
        getFriends: function(user,callback){
            if(!user){
                user=""
            }
            return getFriends(user,callback);
        },
        acceptFriendRequest: function(userFrom,callback){
            acceptFriendRequest(userFrom,callback);
        },
        refuseFriendRequest: function(userFrom,callback){
            refuseFriendRequest(userFrom,callback);
        },
        deleteFriendRequest: function(userTo,callback){
            deleteFriendRequest(userTo,callback);
        },
        sendFriendRequest: function(userTo,callback){
            sendFriendRequest(userTo,callback);
        },
        markFRAsRead: function(userFrom,callback){
            markFRAsRead(userFrom,callback);
        },
        blockUser: function(user,callback){
            blockUser(user,callback);
        },
        unblockUser: function(user,callback){
            unblockUser(user,callback);
        },
        deleteFriend: function(user,callback){
            deleteFriend(user,callback);
        },
        getFriendRequests: function(callback){
            getFriendRequests(callback);
        },
        getSentFriendRequests: function(callback){
            getSentFriendRequests(callback);
        },
        getBlockedUsers: function(callback){
            getBlockedUsers(callback);
        },
        getAllUsers: function(needle,callback){
            getAllUsers(needle,callback);
        },
        getExactUsers: function(needle,callback){
            getExactUsers(needle,callback);
        },

        setScore: function(score,map,callback){
            if (inspectIsOpen()){
                console.error("Stop trying to cheat!!");
                //possibly implement a strikes system?
                return false;
            }  
        },
        isHighScore: function(user,score,map,callback){
            isHighScore(user,score,map,callback);
        },
        userRank: function(user,map,timespan,callback){
            userRank(user,map,timespan,callback);
        },
        getUserHighScores(user,callback){
            getUserHighScores(user,callback);
        },
        getAllMaps: function(callback){
            getAllMaps(callback);
        }
        
    }
})();
