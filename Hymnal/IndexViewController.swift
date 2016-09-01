//
//  IndexViewController.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/14/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit

import UIKit
import CoreData

class IndexViewController: UICollectionViewController, UINavigationControllerDelegate, NSFetchedResultsControllerDelegate {
    
    @IBOutlet weak var indexCollectionView: UICollectionView!

    let appDelegate = UIApplication.shared.delegate as! AppDelegate
    var managedObjectContext: NSManagedObjectContext? = nil
    
    var hymns = [NSManagedObject]()
    var theHymn: NSManagedObject!
    var selectedIndexPath: IndexPath = []
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        self.managedObjectContext = appDelegate.managedObjectContext
        
        
        
        let managedContext = self.fetchedResultsController.managedObjectContext
        let fetchRequest = NSFetchRequest<NSFetchRequestResult>(entityName: "Hymn")
        let sortDescriptors = [NSSortDescriptor(key: "number", ascending:true, selector: #selector(NSString.localizedStandardCompare))]
        fetchRequest.sortDescriptors = sortDescriptors
        
        do {
            let results =
                try managedContext.fetch(fetchRequest)
            hymns = results as! [NSManagedObject]
            
        } catch let error as NSError {
            print("Could not fetch \(error), \(error.userInfo)")
        }
        
        if (hymns.count == 0) {
            resetToDefaultData()
        }
        
    }
    
    override func viewWillAppear(_ animated: Bool) {
        self.navigationController?.delegate = self;
    }
    
    func createHymn(title: String, number: Int) -> NSManagedObject {
        let managedContext = self.fetchedResultsController.managedObjectContext
        let entity =  NSEntityDescription.entity(forEntityName: "Hymn",
                                                 in:managedContext)
        
        let hymn = NSManagedObject(entity: entity!,
                                   insertInto: managedContext)
        hymn.setValue(title, forKey: "title")
        hymn.setValue(number, forKey: "number")
        
        do {
            try managedContext.save()
            hymns.append(hymn)
            //self.versesTableView.reloadData()
        } catch let error as NSError  {
            print("Could not save \(error), \(error.userInfo)")
        }
        return hymn
    }
    
    func createVerse(order: Int, verseNumber: String, isChorus: Bool, text: String, hymn: NSManagedObject) {
        
        let managedContext = self.fetchedResultsController.managedObjectContext
        let entity =  NSEntityDescription.entity(forEntityName: "Verse",
                                                 in:managedContext)
        
        let verse = NSManagedObject(entity: entity!,
                                    insertInto: managedContext)
        verse.setValue(order, forKey: "order")
        verse.setValue(verseNumber, forKey: "verseNumber")
        verse.setValue(isChorus, forKey: "isChorus")
        verse.setValue(text, forKey: "text")
        //print(hymn)
        verse.setValue(hymn, forKey: "hymn")
        
        do {
            try managedContext.save()
            //self.versesTableView.reloadData()
        } catch let error as NSError  {
            print("Could not save \(error), \(error.userInfo)")
        }
        
    }
    
    func resetToDefaultData() {
        
        // Repeat this sample data 100 times to create 600 hymns
        for index in 0...100 {
            
            var i = index*7
            if (i==0) {
                i = 1
            }
            
            let hymn1 = createHymn(title: "Glory be to God the Father", number: i)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"Glory be to God the Father,\nAnd to Christ the Son,\nGlory to the Holy Spirit—\nEver One.", hymn: hymn1)
            createVerse(order:1, verseNumber:"2", isChorus:false, text:"As we view the vast creation,\nPlanned with wondrous skill,\nSo our hearts would move to worship,\nAnd be still.", hymn: hymn1)
            createVerse(order:2, verseNumber:"3", isChorus:false, text:"But, our God, how great Thy yearning\nTo have sons who love\nIn the Son e’en now to praise Thee,\nLove to prove!", hymn: hymn1)
            createVerse(order:3, verseNumber:"4", isChorus:false, text:"’Twas Thy thought in revelation,\nTo present to men\nSecrets of Thine own affections,\nTheirs to win.", hymn: hymn1)
            createVerse(order:4, verseNumber:"5", isChorus:false, text:"So in Christ, through His redemption\n(Vanquished evil powers!)\nThou hast brought, in new creation,\nWorshippers!", hymn: hymn1)
            
            
            let hymn2 = createHymn(title: "Glory, glory, glory, praise and adoration", number: i+1)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"Glory, glory, glory, praise and adoration!\nHear the anthems swelling out thro’ all eternity!\nFather, Son, and Spirit—God in revelation—\n  Prostrate each soul before the Deity!", hymn: hymn2)
            createVerse(order:1, verseNumber:"2", isChorus:false, text:"Father, source of glory, naming every fam’ly;\nAnd the Son upholding all by His almighty power;\nHoly Spirit, filling the vast scene of glory—\n  O glorious Fulness, let our hearts adore!", hymn: hymn2)
            createVerse(order:2, verseNumber:"3", isChorus:false, text:"God supreme, we worship now in holy splendour,\nHead of the vast scene of bliss, before Thy face we fall!\nMajesty and greatness, glory, praise and power\n  To Thee belong, eternal Source of all!", hymn: hymn2)
            createVerse(order:3, verseNumber:"3", isChorus:false, text:"His oath, His covenant, His blood,\nSupport me in the whelming flood;\nWhen all around my soul gives way,\nHe then is all my hope and stay.", hymn: hymn2)
            createVerse(order:4, verseNumber:"4", isChorus:false, text:"When He shall come with trumpet sound,\nOh, may I then in Him be found;\nIn Him, my righteousness, alone,\nFaultless to stand before the throne.", hymn: hymn2)
            
            let hymn3 = createHymn(title: "Come, Thou Almighty King", number: i+2)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"Come, Thou Almighty King,\nHelp us Thy name to sing,\n  Help us to praise.\nFather, all glorious,\nO’er all victorious,\nCome, and reign over us,\n  Ancient of Days.", hymn: hymn3)
            createVerse(order:1, verseNumber:"2", isChorus:false, text:"Come, Thou incarnate Word,\nGird on Thy mighty sword,\n  Our prayer attend:\nCome, and Thy people bless,\nAnd give Thy word success;\nSpirit of holiness,\n  On us descend.", hymn: hymn3)
            createVerse(order:2, verseNumber:"3", isChorus:false, text:"Come, holy Comforter,\nThy sacred witness bear\n  In this glad hour:\nThou who Almighty art,\nNow rule in every heart,\nAnd ne’er from us depart,\n  Spirit of power.", hymn: hymn3)
            createVerse(order:3, verseNumber:"4", isChorus:false, text:"To Thee, great One in Three,\nEternal praises be\n  Hence evermore.\nThy sov’reign majesty\nMay we in glory see,\nAnd to eternity\n  Love and adore.", hymn: hymn3)
            
            
            let hymn4 = createHymn(title: "Father of heav’n, whose love profound", number: i+3)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"Father of heav’n, whose love profound\nA ransom for our souls hath found,\nBefore Thy throne we sinners bend;\nTo us Thy pard’ning love extend.", hymn: hymn4)
            createVerse(order:1, verseNumber:"2", isChorus:false, text:"Almighty Son, incarnate Word,\nOur Prophet, Priest, Redeemer, Lord,\nBefore Thy throne we sinners bend;\nTo us Thy saving grace extend.", hymn: hymn4)
            createVerse(order:2, verseNumber:"3", isChorus:false, text:"Eternal Spirit, by whose breath\nThe soul is raised from sin and death,\nBefore Thy throne we sinners bend;\nTo us Thy quickening power extend.", hymn: hymn4)
            createVerse(order:3, verseNumber:"4", isChorus:false, text:"Thrice holy—Father, Spirit, Son;\nMysterious Godhead, Three in One,\nBefore Thy throne we sinners bend;\nGrace, pardon, life to us extend.", hymn: hymn4)
            
            
            let hymn5 = createHymn(title: "God, our Father, we adore Thee", number: i+4)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"God, our Father, we adore Thee!\nWe, Thy children, bless Thy Name!\nChosen in the Christ before Thee,\nWe are “holy without blame.”\nWe adore Thee! we adore Thee!\nAbba’s praises we proclaim!\nWe adore Thee! we adore Thee!\nAbba’s praises we proclaim!", hymn: hymn5)
            createVerse(order:1, verseNumber:"2", isChorus:false, text:"Son Eternal, we adore Thee!\nLamb upon the throne on high!\nLamb of God, we bow before Thee,\nThou hast brought Thy people nigh!\nWe adore Thee! we adore Thee!\nSon of God, Who came to die!\nWe adore Thee! we adore Thee!\nSon of God, Who came to die!", hymn: hymn5)
            createVerse(order:2, verseNumber:"3", isChorus:false, text:"Father, Son and Holy Spirit—\nThree in One! we give Thee praise!\nFor the riches we inherit,\nHeart and voice to Thee we raise!\nWe adore Thee! we adore Thee!\nThee we bless, through endless days!\nWe adore Thee! we adore Thee!\nThee we bless, through endless days!", hymn: hymn5)
            
            let hymn6 = createHymn(title: "My hope is built on nothing less", number: i+5)
            createVerse(order:0, verseNumber:"1", isChorus:false, text:"My hope is built on nothing less\nThan Jesus Christ, my righteousness;\nI dare not trust the sweetest frame,\nBut wholly lean on Jesus’ name.", hymn: hymn6)
            createVerse(order:1, verseNumber:"", isChorus:true, text:"On Christ, the solid Rock, I stand;\nAll other ground is sinking sand,\nAll other ground is sinking sand.", hymn: hymn6)
            createVerse(order:2, verseNumber:"2", isChorus:false, text:"When darkness veils His lovely face,\nI rest on His unchanging grace;\nIn every high and stormy gale,\nMy anchor holds within the veil.", hymn: hymn6)
            createVerse(order:3, verseNumber:"3", isChorus:false, text:"His oath, His covenant, His blood,\nSupport me in the whelming flood;\nWhen all around my soul gives way,\nHe then is all my hope and stay.", hymn: hymn6)
            createVerse(order:4, verseNumber:"4", isChorus:false, text:"When He shall come with trumpet sound,\nOh, may I then in Him be found;\nIn Him, my righteousness, alone,\nFaultless to stand before the throne.", hymn: hymn6)
            
        }
        print("HYMNS COUNT", hymns.count)
    }
    
    
    
    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
    
    override func prepare(for segue: UIStoryboardSegue, sender: Any?) {
        let indexPath = indexCollectionView.indexPath(for: sender as! UICollectionViewCell)
        
        if((indexPath) != nil) {
            let hvc: HymnViewController = (segue.destination as? HymnViewController)!
            selectedIndexPath = indexPath!
            hvc.selectedIndexPath = indexPath!
            //hvc.useLayoutToLayoutNavigationTransitions = true
        }
    }
    

    
    @IBAction func unwindToIndex(segue:UIStoryboardSegue) {
        print("Attempting to unwind")
    }
    
    // In a storyboard-based application, you will often want to do a little preparation before navigation
//    - (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
//    {
//    // Get the new view controller using [segue destinationViewController].
//    // Pass the selected object to the new view controller.
//    NSIndexPath *indexPath = [[self.collectionView indexPathsForSelectedItems] firstObject];
//    
//    if (indexPath) {
//    FJDetailViewController *dvc = segue.destinationViewController;
//    dvc.useLayoutToLayoutNavigationTransitions = YES;
//    
//    dvc.itemCount = [_itemCounts[indexPath.section] integerValue];
//    dvc.color = indexPath.section;
//    _selectedItem = indexPath.item;
//    }
//    
//    }
    
    // UICollectionView methods
    
    // MARK: UICollectionViewDataSource
    
    override func numberOfSections(in collectionView: UICollectionView) -> Int {
        // #warning Incomplete implementation, return the number of sections
        return 1
    }
    
    
    override func collectionView(_ collectionView: UICollectionView, numberOfItemsInSection section: Int) -> Int {
        return hymns.count
    }
    
    //    interspacing
    func collectionView(_ collectionView: UICollectionView,
                        layout collectionViewLayout: UICollectionViewLayout,
                        minimumInteritemSpacingForSectionAt section: Int) -> CGFloat {
        return 1.0
    }
    
    func collectionView(_ collectionView: UICollectionView, layout
        collectionViewLayout: UICollectionViewLayout,
                        minimumLineSpacingForSectionAt section: Int) -> CGFloat {
        return 15.0
    }
    
    override func collectionView(_ collectionView: UICollectionView, cellForItemAt indexPath: IndexPath) -> UICollectionViewCell {
        let cell = indexCollectionView.dequeueReusableCell(withReuseIdentifier: "IndexCell", for: indexPath) as! IndexCollectionViewCell
        cell.initWith(theHymn: hymns[indexPath.row])
        
        return cell
    }
    
    func navigationController(_ navigationController: UINavigationController, didShow viewController: UIViewController, animated: Bool) {
        
        if (viewController.isKind(of: HymnViewController.self)) {
            let hvc: HymnViewController = (viewController as? HymnViewController)!
            hvc.collectionView?.dataSource = hvc
            hvc.collectionView?.delegate = hvc
            print("scroll to indexPath", selectedIndexPath)
            //hvc.collectionView?.scrollToItem(at: selectedIndexPath, at: UICollectionViewScrollPosition.centeredVertically, animated: true)
            
        }
        else {
            self.collectionView?.dataSource = self
            self.collectionView?.delegate = self
        }
    }
    
    // MARK: - Fetched results controller
    
    var fetchedResultsController: NSFetchedResultsController<Hymn> {
        if _fetchedResultsController != nil {
            return _fetchedResultsController!
        }
        
        let fetchRequest: NSFetchRequest<Hymn> = Hymn.fetchRequest()
        
        // Set the batch size to a suitable number.
        fetchRequest.fetchBatchSize = 100
        
        // Edit the sort key as appropriate.
        let sortDescriptor = NSSortDescriptor(key: "number", ascending: true)
        
        fetchRequest.sortDescriptors = [sortDescriptor]
        
        // Edit the section name key path and cache name if appropriate.
        // nil for section name key path means "no sections".
        let aFetchedResultsController = NSFetchedResultsController(fetchRequest: fetchRequest, managedObjectContext: self.managedObjectContext!, sectionNameKeyPath: nil, cacheName: nil)
        aFetchedResultsController.delegate = self
        _fetchedResultsController = aFetchedResultsController
        
        do {
            try _fetchedResultsController!.performFetch()
        } catch {
            // Replace this implementation with code to handle the error appropriately.
            // fatalError() causes the application to generate a crash log and terminate. You should not use this function in a shipping application, although it may be useful during development.
            let nserror = error as NSError
            fatalError("Unresolved error \(nserror), \(nserror.userInfo)")
        }
        
        return _fetchedResultsController!
    }
    var _fetchedResultsController: NSFetchedResultsController<Hymn>? = nil
    
    
    
}

