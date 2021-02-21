HOST = ''

from os import system
from datetime import datetime

system("title PyDBGServ")
#system('color 1f')
#system('setterm -background white -foreground white -store')

from threading import Thread, Lock

PORTS = {
    #PORT : False
}
DEF_PORT = 2122

__lock = Lock()
def user_inp_listener():
    print ("Type 'exit' to quit")
    print ("use 'start/stop #PORT(2122)' for server threads")
    while True:
        print (">")
        inp = input();
        if (inp == 'exit'):
            print ("exiting ...")
            for p in PORTS:
                if (PORTS[p]):
                    with socket.create_connection((('127.0.0.1', p))) as s:
                        s.sendall(b'#BYE')
                    PORTS[p] = False
            break;
        elif(inp.startswith("start ")):
            for p in inp[6:].split(','):
                p = int(p.strip())
                print(p)
                Thread(target = server_th, args = (p,)).start()
                PORTS[p] = True

thread = Thread(target = user_inp_listener)
thread.start()

import socket
def server_th(port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        __lock.acquire();
        print ("Binding " + str(port) + '...',);
        try:
            s.bind((HOST, port))
            s.listen(1)
        except:
            print ("Binding " + str(port) + ' FAILED!!!');
            return;
        finally:
            __lock.release();
        br = False;
        while True:
            print ("["+ datetime.now().strftime("%H:%M:%S") + "]   <" + str(port) + ">")
            print ("-" * 70)
            conn, addr = s.accept()
            with conn:
                print('Connected by', addr)
                while True:
                    data = conn.recv(1024)
                    dt = data.decode()
                    print ('\n' + dt.strip(' \t\n\r'))
                    if not dt:
                        print ('=====');
                        break
                    if (dt == '#BYE'):
                        print ('Stop listenting on '+ str(port) +'. Bye!');
                        br = True;
                        break;
            if br: break;

Thread(target = server_th, args = (DEF_PORT,)).start()
PORTS[DEF_PORT] = True

print("OK");