

#CHA:xskuto00

import os
import sys
import re

#indikatory argumentu
dire = False
array_files = []
help_array_files = []
input_file = ""
output_file = ""
p_xml_k = 4
m_par = 0
count = 0

#indikatory parametru
helpm = inputf = output = prett_xml = n_inline = max_par = n_duplic = remov_whitesp = False

#vysledek
vystup = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"

#--------------------------------------------------------------
#vytiskne chybovou zpravu, a ukonci skript z danym navratovym kodem
def message_bad(zprava, navr_kod):
    print(zprava, file=sys.stderr)
    print("Pro napovedu --help", file=sys.stderr)
    sys.exit(navr_kod)
#--------------------------------------------------------------
#--------------------------------------------------------------
#vytiskne napovedu a ukonci skript s exit 0
def message_help():
    print("Projekt do predmetu IPP verze CHA")
    print("Program provadi analyzvu .h souboru jazyka C a vytvari databazi funkci")
    print("Pouziti: cha.py [--help] [--input=fileordir] [--output=filename] [--pretty-xml=[k]] \n[--no-inline] [--max-par=n] [--no-duplicates] [--remove-whitespace]")
    print("Vsechny parametry jsou nepovinne a na jejich poradi nezalezi")
    print("\t--help Vypise tuto napovedu")
    print("\t--input=fileordir Vstupni soubor/adresar, pokud je zadan adresar\n\t\ttak se analyzuji vsechny *.h soubory v danem adresari i jeho\n\t\tpodadresarich, pri nezadani tohoto parametru se pouzije aktualni adresar")
    print("\t--output=filename Vystupni soubor, pri nezadani se vypisuje na standartni vystup")
    print("\t--pretty-xml=k Odsazeni v xml o k mezer, k muze chybet (pozije se hodnota 4), k musi byt nezaporne cele cislo")
    print("\t--no-inline Funkce deklarovane ze specifikatorem inline budou vynechany")
    print("\t--max-par=n Berou se v uvahu pouze funkce s n ci mene parametru, n musi byt zadano, n musi byt cele nezaporne cislo")
    print("\t--no-duplicates Duplicitni nazvy funkci se budou ignorovat")
    print("\t--remove-whitespace z navratovych typu se odstrani mezery a z parametru se bile znaky zastoupi mezerou")
    sys.exit(0)
#--------------------------------------------------------------
#--------------------------------------------------------------
#Funkce zpracuje parametry, pohadala jsem se s argparse
#zadny parametr nemusi byt zadany, 
def arg_pars():
    global dire, input_file, output_file, p_xml_k, m_par
    global helpm, inputf, output, prett_xml, n_inline, max_par, n_duplic, remov_whitesp
         
    del sys.argv[0] #odstranit nazev programu
  
    if (len(sys.argv) < 1): #zadne parametry nezadane
        dire = True
        output_file = sys.stdout
    elif (len(sys.argv) == 1 and sys.argv[0] == "--help"): #zadan jeden parametr a to help
        helpm = True
    else: #je vice parametru zadanych, nebo help neni sam, nebo to neni help
        for argument in sys.argv:
            if (argument[0:8] == "--input=" and (not inputf)): #--input zadany
                inputf = True
                input_file = argument[8:] #zikat argument
                if (len(input_file) == 0):
                    message_bad("Chyba v parametru --input=", 1)
            elif (argument[0:9] == "--output=" and (not output)): #--output zadany
                output = True
                output_file = argument[9:]
                if (len(output_file) == 0):
                    message_bad("Chyba v parametru --output=", 1)
            elif (argument[0:12] == "--pretty-xml" and (not prett_xml)): #--pretty-xml zadany, POZOR hodnota muze chybet
                prett_xml = True
                if (argument[12:] == ""): #muze byt prazdne
                    p_xml_k = 4
                else:
                    try:
                        p_xml_k = int(argument[13:]) #pokus o prevod a int
                    except:
                        message_bad("Chyba v parametru --pretty-xml=", 1) #neni to int
            elif (argument == "--no-inline" and (not n_inline)): #--no-inline je zadany
                n_inline = True
            elif (argument[0:10] == "--max-par=" and (not max_par)): #--max-par= zadany
                max_par = True
                try:
                    m_par = int(argument[10:])
                except:
                    message_bad("Chyba v parametru --max-par=", 1) #neni to int
            elif (argument == "--no-duplicates" and (not n_duplic)):
                n_duplic = True
            elif (argument == "--remove-whitespace" and (not remov_whitesp)):
                remov_whitesp = True
            else:
                message_bad("Chyba v parametrech", 1)
    
    if (m_par < 0 or p_xml_k < 0): # nemuzou to byt zaporna cisla
        message_bad("Chyba v parametrech", 1)           
#--------------------------------------------------------------
#--------------------------------------------------------------
#pomoci rekurze prohledava adresare i podadresare a hleda soubory s koncovkou .h nebo .H
def get_file(directory):
    global array_files, help_array_files 
    for files in os.listdir(directory):  #najit vsechny polozky v adresari
        files = os.path.join(directory, files)
        if(os.path.isdir(files)):  #je to adresar, rekurzivne najit sobory
            get_file(files)
        elif (os.path.isfile(files)): #je to soubor + ma spravnou koncovku?
            c_ext = os.path.splitext(files)[1]
            if (c_ext == ".h" or c_ext == ".H"):
                array_files.append(files)
#--------------------------------------------------------------
#--------------------------------------------------------------
#korekce nazvu souboru, maji byt relativni k zadanemu adresari
def correct_files(soubor):
    global input_file
    
    help_me = soubor.replace(input_file, "", 1)
    if (help_me[0:1] == '/'):
        help_me = help_me[1:] 
        
    return help_me
#--------------------------------------------------------------
#--------------------------------------------------------------
#dle paramateru --input, zkontroluje zda dany soubor/adresar existuje, v pripade adresaru najde soubory
def find_file():
    global inputf, input_file, array_files, dire
    
    if (inputf): #byl zadany input
        if (os.path.exists(input_file)): #soubor/adresar existuje
            if (os.path.isfile(input_file)): #je to jenom jeden soubor!
                array_files.append(input_file)
            else:
                dire = True
        else:
            message_bad("Zadany soubor/adresar neexistuje", 2)
    else: #--input nezadan pracujeme z aktualnim adresarem
        dire = True
        input_file = os.getcwd()
        
    if (dire): #pracuji s adresarem musim dostat vsechny soubory
        get_file(input_file)  
#--------------------------------------------------------------
#--------------------------------------------------------------
#vymaze vsechny makra, pozor makro muze pokracovat na dalsim rakdu, prevzato s meho CST projektu 
def DESTROY_Macro(file_text):
    line_text = file_text.split("\n") #pozor ztratim nove radky
    stav = 0
    makro_pokr = False
    fulltext = ""
    for line in line_text: #radky
        line += '\n' #pridat ztracence \n, bude tam jeden navic, ale snad to nebude vadit
        if (line[0:1] == "#" or makro_pokr):
            for c in line: #znaky
                if (stav == 0 and c == '\\'):   #nasla jsem \, mozne pokracovani makra na dlasim radku
                    stav = 1
                elif (stav == 1 and c == "\n"): #jo bylo to pokracovani makra na dalsim radku
                    stav = 0
                    makro_pokr = True
                else:
                    stav = 0
                    makro_pokr = False
        else:
            fulltext += line
    return fulltext    
#--------------------------------------------------------------
#--------------------------------------------------------------
#odstrani se komentare, pozor na komentate, smaze i pokacujici komentare pomoci \, obycejne retezce necha napokoji
#prevzato z predchoziho CST projektu
#zrada ve smyslu jak je \ tak nasledujici znak naplati at u retezcu, literalu i komentaru
def DESTROY_Comment(file_text):
    stav = 0
    fulltext = ""
    for c in file_text: #po znacich
        if (stav == 0 and c == '"'): #zacatek retezce
            stav = 2
            fulltext += c
        elif (stav == 0 and c == '/'): #mozny zacatek komentare
            stav = 1
        elif (stav == 0 and c == '\''): #zacatek znakoveho literalu
            stav = 7
            fulltext += c
        elif (stav == 0 and (c != '"' or c != '/' or c != '\'')): #nezacal ani kometar ani retezec
            stav = 0
            fulltext += c
        elif (stav == 2 and c == '\\'):  #POZOR mozny pokus o zradu v retezci
            stav = 8
            fulltext += c
        elif (stav == 2 and c == '"'): #konec retezce
            stav = 0
            fulltext += c
        elif (stav == 2 and (c != '"' or c != '\\')): #znaky uvnitr retezce
            stav = 2
            fulltext += c
        elif (stav == 8 and c == '"'): #ZRADA!! jsme stale uvnitr retezce
            stav = 2
            fulltext += c
        elif (stav == 8 and c == '\\'): #znak \ se tam opakuje
            stav = 8
            fulltext += c
        elif (stav == 8 and (c != '"' or c != '\\')): #zadna zrada asi jenom escape sekvence 
            stav = 2
            fulltext += c
        elif (stav == 7 and c == '\\'): #POZOR mozny pokus o zradu ve znakovem literalu
            stav = 9
            fulltext += c
        elif (stav == 7 and c == '\''): #konec znakoveho literalu
            stav = 0
            fulltext += c
        elif (stav == 7 and (c != '\'' or c != '\\')): #neco uvnitr znakoveho literalu
            stav = 7 
            fulltext += c
        elif (stav == 9 and c == '\''): #ZRADA!! jsme stale uvnitr znakovem literalu
            stav = 7
            fulltext += c
        elif (stav == 9 and c != '\''): #je tam jiny znak
            stav = 7
            fulltext += c
        elif (stav == 1 and c == '/'): #potvrzeno je to RADKOVY KOMENT
            stav = 3
        elif (stav == 1 and c == '*'): #potvrzeno je to BLOKOVY KOMENT
            stav = 4
        elif (stav == 1 and (c != '/' or c != '*')): #neco jinaciho...mozna deleni
            stav = 0
            fulltext += '/'
            fulltext += c
        elif (stav == 3 and c == "\n"): #timtot konci radkovy retezec
            stav = 0
        elif (stav == 3 and c == '\\' ): #POZOR mozne zalomeni na dalsi radek
            stav = 5
        elif (stav == 3 and (c != "\n" or c != '\\')): #neco uprostred kometaru
            stav = 3
        elif (stav == 5 and c == "\n"): #radkovy komentar je i na dalsim radku
            stav = 3
        elif (stav == 5 and c == '\\'): #znak mozneho zalomeni se opakuje
            stav = 5
        elif (stav == 5 and (c != "\n" or c != '\\')): #zadne zalomeni, jenom si to z nas delalo srandu
            stav = 3
        elif (stav == 4 and c == '*'): #mozny konec blokoveho komentare
            stav = 6
        elif (stav == 4 and c != '*'): #vsechny ostatni znaky
            stav = 4
        elif (stav == 6 and c == '*'): #znak * se opakuje
            stav = 6
        elif (stav == 6 and c == '/'): #konec blokoveho komentare
            stav = 0
        elif (stav == 6 and (c != '*' or c != '/')): # bylo to jenom obyc *
            stav = 4
              
    return fulltext    
#--------------------------------------------------------------
#--------------------------------------------------------------
#odstrani retezce, komentraje jiz budou odstraneny, nemusime je uvazovat, prevzate z meho CST projektu
def DESTROY_String(file_text):
    stav = 0
    fulltext = ""
    for c in file_text: #po znacich
        if (stav == 0 and c == '"'): #zacatek retezce
            stav = 2
        elif (stav == 0 and c == '\''): #zacatek znakoveho literalu
            stav = 7
        elif (stav == 0 and (c != '"' or c != '\'')): #nezacal ani kometar ani retezec
            stav = 0
            fulltext += c
        elif (stav == 2 and c == '\\'):  #POZOR mozny pokus o zradu v retezci
            stav = 8
        elif (stav == 2 and c == '"'): #konec retezce
            stav = 0
        elif (stav == 2 and (c != '"' or c != '\\')): #znaky uvnitr retezce
            stav = 2
        elif (stav == 8 and c == '"'): #ZRADA!! jsme stale uvnitr retezce
            stav = 2
        elif (stav == 8 and c == '\\'): #znak \ se tam opakuje
            stav = 8
        elif (stav == 8 and (c != '"' or c != '\\')): #zadna zrada asi jenom escape sekvence 
            stav = 2
        elif (stav == 7 and c == '\\'): #POZOR mozny pokus o zradu ve znakovem literalu
            stav = 9
        elif (stav == 7 and c == '\''): #konec znakoveho literalu
            stav = 0
        elif (stav == 7 and (c != '\'' or c != '\\')): #neco uvnitr znakoveho literalu
            stav = 7
        elif (stav == 9 and c == '\''): #ZRADA!! jsme stale uvnitr znakovem literalu
            stav = 7
        elif (stav == 9 and c != '\''): #je tam jiny znak
            stav = 7
            
    return fulltext
#--------------------------------------------------------------
#--------------------------------------------------------------
#odstrani bloky, at uz po strukturach nebo u definic funkci, snad se tim nic nepokazi, a nebude tam neco zradneho
def DESTROY_Blocks(file_text):
    stav = 0
    fulltext = ""
    for c in file_text:
        if (stav == 0 and c == '{'): #dostavame se do bloku
            blocks = 1 #prvni zanoreni
            stav = 1
        elif (stav == 0 and c != '{'): #nedostali jsem se do bloku, obyc text
            stav = 0
            fulltext += c
        elif (stav == 1): #jsme v bloku, muzeme se neomezene zanorovat
            if(blocks):
                if (c == '}'): #vynorujeme se z nejakeho bloku
                    blocks -= 1
                elif (c == '{'): #dostali jsme se do dalsiho bloku
                    blocks += 1
                else: #jsme uvnitr bloku
                    stav = 1
                    
                if (blocks == 0): #dostali jsme se uplne ven
                    stav = 0
                    fulltext += ';'
    
    return fulltext
#--------------------------------------------------------------
#--------------------------------------------------------------
# otevre soubor, vyhleda co ma, uzavre soubor
def play_Seek(soubor):
    global vystup, n_inline, n_duplic, remov_whitesp, max_par, m_par, input_file, p_xml_k, prett_xml   
    try:
        des = open(soubor, 'r')  #defaultne se to otevre v utf-8
    except:
        message_bad("Nektery ze vstupnich souboru nelze otevrit", 2)
        
    text = des.read()    #precist a ulozit cely soubor
    des.close()
    
    #odstranit makra, komentare a retezce
    text = DESTROY_Macro(text)
    text = DESTROY_Comment(text)
    text = DESTROY_String(text)
    
    #odstranit definice struct, union, enum, typedef, prevzato s CST projektu, snad to nebude vadit
    new_text = text.splitlines(True)
    text = ""
    stav = 0
    funkce = False
    for t in new_text: #prochazet po radcich
        stav = 0
        if (re.search(r"\b(typedef|struct|enum|union)\b", t)): #najdeme tam neco z toho
            for c in t:
                if (stav == 0 and c == '('): #neni to def/dek stuktury, je to ve funkci
                    funkce = True
                    break
                if (stav == 0 and c == '{'):
                    text += c
                    stav = 1
                elif (stav == 1):
                    text += c
        else: #neni to nic z toho
            text += t
            
        if (funkce): #je to ve funkci, ten radek take musime pridat
            text += t
            funkce = False
            
    #odstrani se bloky, ikdyz videla jsem deklaraci funkce v definici funkce....
    text = DESTROY_Blocks(text)
    text = re.sub(r";.*;", "", text) #konecna uprava - potomhle bych mela mit jenom  hlavicky funkci...snad 
    #zde jsou vsechny hlavicky
    spilt_text = text.split(';')
    spilt_text.pop() #odstranim ten posledni prvek, ktery je vzdy po rozdeleni prazdny, doufam, ze bude prazdny nebo v nem nebude zadna funkce
    
    names = []
    #prochazet jednotlive hlavicky
    for hlavicka in spilt_text:
        varargs = "no"
        
        #odstranit bile znaky z pred a konce hlavicky
        hlavicka = re.sub(r"^\s*", "", hlavicka)
        hlavicka = re.sub(r"\s*$", "", hlavicka)

        #-----------------NAVRATOVY TYP FUNKCE + NAZEV FUNKCE------------------
        predek = ""
        #ziskat nazev a navratovy typ
        for c in hlavicka:
            if (c == '('):
                break
            else:
                predek += c
        
        #mezi nazvem a zavorkou mohou byt nejake mezery, na zacatku by uz nic byt nemelo
        predek = re.sub(r"\s*$", "", predek)
        #pokud byl zadan inline, tak ingnorujeme funkce s inline
        if (n_inline):
            inline = 0
            inline = re.findall(r"\binline\b", predek)
            if (len(inline) > 0):
                continue
        #ziskat jmeno funkce, melo by by ulozene v function_name[0]                 
        function_name = re.findall(r"\b\w+$", predek)
        name = function_name[0]
       
        duplic = False
        #ukladat do listu nazvy, pokud budou duplicity tak to s tim kontorlovat 
        if(n_duplic):
            if (len(names) == 0):#nic jeste nebylo dano
                names.append(name)
            else:
                for n in names:
                    if (duplic):
                        break
                    if (n == name):
                        duplic = True
                    else:
                        duplic = False
                        
            if (duplic):
                continue
            else:
                names.append(name) 
                
        rettype = ""     
        #odstranit jmeno funkce, pozor mohla by tam zbyt nejaka mezera
        predek = re.sub(r"\b"+name+r"\b", "", predek)
        predek = re.sub(r"\s*$", "", predek)
        
        #pri trose stesti jedine co zbylo byl navratovy typ funkce
        if(remov_whitesp):  #uprav mezery, uprav mezery mezi id *, * id, * * (id*, *id, **)
            predek = re.sub(r"\s+", " ", predek)   #+ musi byt, * by mi udelala mezeru i mezi pismeny
            predek = re.sub(r"\s*\*", "*", predek)
            predek = re.sub(r"\*\s*", "*", predek) 
     
        rettype = predek
        #-----------------PARAMETRY A JEJICH NAVRATOVE TYPY -------------------
        # POZOR NA VOID + PRAZDNE ZAVORKY
        parametr = False
        vnitrek = ""
        param_void = False
        pocet_param = 0
        params = []
        vsio = False
        types = []
        for c in hlavicka: #duvod tak divneho testovani - aby se mi do vnitrku nedostaly zavorky ()
            if (c == ')'):
                parametr = False
            if (parametr):
                vnitrek += c
            if (c == '('):
                parametr = True
        
        #mezi levou a pravou zavorkou by na zacatku mohly byt mezery, dle vseho
        vnitrek = re.sub(r"^\s*", "", vnitrek)
        vnitrek = re.sub(r"\s*$", "", vnitrek)


        if(len(vnitrek) == 0): #nic tam neni, zadne parametry
            param_void = True
            vsio = True #muzu zapsat danou funkci
        elif(len(re.findall(r"\bvoid\b", vnitrek)) > 0 and len(vnitrek) == 4): #je tam void, ale jenom void a nic jineho, pry existuje void *
            param_void = True
            vsio = True #muzu zapsat danou funkci
        else: #neco tam je
            params = vnitrek.split(',')
            for param in params:
                param = re.sub(r"^\s*", "", param)
                param = re.sub(r"\s*$", "", param)

                if (len(re.findall(r"\.\.\.", param)) > 0): #promenne pamametry se ignoruji, ani se nezapocitavaji do poctu
                    varargs = "yes"
                    continue
                param_names = []
                param_name = ""
                
                pocet_param += 1 
                #jmeno paramentru, nasledne ho zlikvidovat, nepotrebujeme ho
                param_names = re.findall(r"\b\w+$", param)
                param_name = param_names[0]
                
                #odtsranit jmeno parametru, mel b zustat pouze typ, snad
                param = re.sub(r"\b"+param_name+r"\b", "", param)
                param = re.sub(r"\s*$", "", param)       
                
                #pri trose stesti jedine co zbylo je typ parametru
                if(remov_whitesp):  #uprav mezery, uprav mezery mezi id *, * id, * * (id*, *id, **)
                    param = re.sub(r"\s+", " ", param)   #+ musi byt, * by mi udelala mezeru i mezi pismeny
                    param = re.sub(r"\s*\*", "*", param)
                    param = re.sub(r"\*\s*", "*", param)
                
                types.append(param)
                
                vsio = True
                  
        #-----------KONECNE KOTROLY, PRIPRAVA VYPISU-------------------#
        if(max_par): #ma funkce <= paramentru
            if (pocet_param <= m_par):
                vsio = True
            else: #danou funkci neuvazujeme
                continue
                
        #potrebuju jmeno souboru a chystam se na zapis
        if (vsio):
            if (dire): #byl zadan adresar, file bude relativni k adresari
                ffile = correct_files(soubor)
            else: #je to soubor, cesta k souboru bude file
                ffile = input_file
        
        #pokud bude pretty, tak uz to odradkovane bude, na konec ale se musi vzdy dat  odratkovani + nezapomen na odsaseni, pouzij mezery
        if (prett_xml):
            pom = p_xml_k
            while(pom): #pridat mezery
                vystup += " "
                pom -= 1
        
        #FUNKCE
        vystup += "<function file=\"" + ffile + "\" name=\"" + name + "\" varargs=\"" + varargs + "\" rettype=\"" + rettype + "\">"
        
        if (prett_xml):
            vystup += "\n"
            
        #PARAMETRY
        if (not param_void):
            i = 0
            for par in types:
                i += 1
                if (prett_xml):
                    pom = 2 * p_xml_k
                    while(pom):
                        vystup += " "
                        pom -= 1
                vystup += "<param number=\"" + str(i) + "\" type=\"" + par + "\" />"
                if (prett_xml):
                    vystup += "\n"
                    
        if (prett_xml):
            pom = p_xml_k
            while(pom): #pridat mezery
                vystup += " "
                pom -= 1
                
        vystup += "</function>"                    
        
        if (prett_xml):
            vystup += "\n"                             
#--------------------------------------------------------------
#--------------------------------------------------------------
#do vystupniho retezce ulozi hlavicku
def xml_hlava():
    global dire, inputf, input_file, vystup, prett_xml
    
    #mame odradkovat
    if (prett_xml):
        vystup += "\n"
        
    #<functions dir="">
    if (inputf):
        if (dire):
            vystup += "<functions dir=\"" + input_file + "\">"
        else:
            vystup += "<functions dir=\"\">"
    else:
        vystup += "<functions dir=\"./\">"
        
    #i nakoci to odradkovat
    if (prett_xml):
        vystup += "\n" 
     
#--------------------------------------------------------------
#--------------------------------------------------------------
#do vystupniho retezce vlozi paticku </functions>
def xml_pata():
    global vystup
    vystup += "</functions>\n"       
#--------------------------------------------------------------      
#--------------------------------------------------------------
#Hlavni funkce, ktera vsechno ridi, At ji provazi Sila!
def main():
    global helpm, help_array_files, array_files, output, output_file, vystup
    
    arg_pars() #zpracovat parametry
    
    if (helpm): #byla napoveda
        message_help()
        
    if (output): #byl zadany soubor na vypis
        try: 
            des = open(output_file, 'w', encoding='utf-8')
        except:
            message_bad("Zadany vystupni soubor se nepodarilo otevrit", 3)
        
    find_file() #zpracovat vstupni soubory
    
    xml_hlava()
    
    for soubor in array_files: #zpracovat kazdy soubor
        play_Seek(soubor)
        
    xml_pata()
       
    if (output): #byl zadany soubor na vypis
        des.write(vystup)
        des.close()
    else:#vypis na obrazovku
        print(vystup, end='')
        
    
#--------------------------------------------------------------
#BEGIN OF THE DOOM!!!!
#aby program nesel pouzit jako modul jineho programu
if __name__ == "__main__":
    main()